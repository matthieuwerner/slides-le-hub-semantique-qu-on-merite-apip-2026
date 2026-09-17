// Command risk-server exposes the risk core over HTTP.
//
// This is Experiment 2 in the talk: the same scoring code as Experiment 3, reached across a
// network boundary instead of a function call. Everything here — the listener, the timeouts,
// the shutdown handling, the health endpoint — is the cost of that boundary. None of it is
// needed by the in-process versions, and cataloguing it is part of the argument.
package main

import (
	"context"
	"errors"
	"log/slog"
	"net/http"
	"os"
	"os/signal"
	"strconv"
	"syscall"
	"time"

	"github.com/matthieuwerner/slides-le-hub-semantique-qu-on-merite-apip-2026/go/internal/riskhttp"
	"github.com/matthieuwerner/slides-le-hub-semantique-qu-on-merite-apip-2026/go/risk"
)

const (
	defaultAddr = ":8081"

	// Generous relative to the work, tight relative to a hung client.
	readHeaderTimeout = 2 * time.Second
	readTimeout       = 5 * time.Second
	writeTimeout      = 5 * time.Second
	idleTimeout       = 60 * time.Second

	shutdownGrace = 10 * time.Second
)

func main() {
	logger := slog.New(slog.NewJSONHandler(os.Stdout, &slog.HandlerOptions{
		Level: parseLevel(os.Getenv("LOG_LEVEL")),
	}))
	slog.SetDefault(logger)

	if err := run(logger); err != nil {
		logger.Error("server stopped with an error", slog.String("error", err.Error()))
		os.Exit(1)
	}
}

func run(logger *slog.Logger) error {
	addr := os.Getenv("RISK_SERVER_ADDR")
	if addr == "" {
		addr = defaultAddr
	}

	// The forest size has to be identical on both sides of every comparison, so it is
	// configured the same way in Go and in PHP: one environment variable, read once.
	if raw := os.Getenv("BOUNDARY_LAB_TREE_COUNT"); raw != "" {
		count, err := strconv.Atoi(raw)
		if err != nil || count <= 0 {
			return errors.New("BOUNDARY_LAB_TREE_COUNT must be a positive integer, got " + raw)
		}
		risk.TreeCount = count
	}

	// Built before the listener opens. If the first request had to build the forest it would
	// carry a cost no later request pays, which would poison both the p99 and the benchmark.
	warmStart := time.Now()
	risk.WarmUp()

	logger.Info("risk engine ready",
		slog.Int("tree_count", risk.TreeCount),
		slog.Duration("warmup", time.Since(warmStart)),
	)

	server := &http.Server{
		Addr:              addr,
		Handler:           riskhttp.NewHandler(logger).Routes(),
		ReadHeaderTimeout: readHeaderTimeout,
		ReadTimeout:       readTimeout,
		WriteTimeout:      writeTimeout,
		IdleTimeout:       idleTimeout,
		ErrorLog:          slog.NewLogLogger(logger.Handler(), slog.LevelWarn),
	}

	// SIGTERM is what a container runtime sends. Draining in-flight authorizations rather
	// than dropping them is not politeness: a dropped authorization is a customer at a
	// terminal watching a spinner.
	ctx, stop := signal.NotifyContext(context.Background(), os.Interrupt, syscall.SIGTERM)
	defer stop()

	serverErr := make(chan error, 1)

	go func() {
		logger.Info("listening", slog.String("addr", addr))
		if err := server.ListenAndServe(); err != nil && !errors.Is(err, http.ErrServerClosed) {
			serverErr <- err
			return
		}
		serverErr <- nil
	}()

	select {
	case err := <-serverErr:
		return err

	case <-ctx.Done():
		logger.Info("shutdown signal received, draining", slog.Duration("grace", shutdownGrace))

		shutdownCtx, cancel := context.WithTimeout(context.Background(), shutdownGrace)
		defer cancel()

		if err := server.Shutdown(shutdownCtx); err != nil {
			return err
		}

		logger.Info("shutdown complete")
		return nil
	}
}

func parseLevel(raw string) slog.Level {
	var level slog.Level
	if err := level.UnmarshalText([]byte(raw)); err != nil {
		return slog.LevelInfo
	}
	return level
}
