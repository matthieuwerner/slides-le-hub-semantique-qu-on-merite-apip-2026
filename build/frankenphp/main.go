// Command frankenphp builds a FrankenPHP binary that includes the Boundary Lab Go extension.
//
// This is what `xcaddy build --with ...` generates for you. It is written out by hand here for
// two reasons, both of which matter more for a repository people will clone than the convenience
// of xcaddy does:
//
//  1. **Reproducibility.** Every version — Caddy, FrankenPHP, the extension — is pinned in the
//     committed go.mod and go.sum next to this file. `xcaddy` resolves versions at build time,
//     so two people running the same command a month apart can get different binaries.
//
//  2. **It has to work.** `go install xcaddy` fails inside the FrankenPHP builder image:
//     `php-config --ldflags` returns `-pie`, and with CGO_LDFLAGS exported the xcaddy build
//     itself dies with "unknown relocation type 313; compiled without -fpic?". Documented in
//     docs/research.md §2.4.
//
// Build (see build/frankenphp/Dockerfile, which does this for you):
//
//	CGO_ENABLED=1 \
//	CGO_CFLAGS="-D_GNU_SOURCE $(php-config --includes)" \
//	CGO_LDFLAGS="$(php-config --ldflags) $(php-config --libs)" \
//	go build -tags=nobadger,nomysql,nopgx,nomercure,nowatcher -o frankenphp .
//
// The -D_GNU_SOURCE is required and is not in the upstream extension documentation: the
// generated C includes php.h, which uses memrchr, a GNU extension.
package main

import (
	caddycmd "github.com/caddyserver/caddy/v2/cmd"

	// The standard Caddy module set, so the resulting binary is a normal Caddy.
	_ "github.com/caddyserver/caddy/v2/modules/standard"

	// FrankenPHP itself, as a Caddy app module.
	_ "github.com/dunglas/frankenphp/caddy"

	// The Boundary Lab risk engine, as a PHP extension written in Go.
	//
	// Importing it for its side effects is the entire integration: the generated
	// risk_generated.go has an init() that calls frankenphp.RegisterExtension(), which is what
	// makes \BoundaryLab\Native\assess() exist in PHP.
	_ "github.com/matthieuwerner/slides-le-hub-semantique-qu-on-merite-apip-2026/go/ext"
)

func main() {
	caddycmd.Main()
}
