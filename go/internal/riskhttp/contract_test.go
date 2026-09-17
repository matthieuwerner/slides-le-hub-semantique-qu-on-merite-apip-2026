package riskhttp

import (
	"os"
	"path/filepath"
	"reflect"
	"strings"
	"testing"

	"github.com/matthieuwerner/slides-le-hub-semantique-qu-on-merite-apip-2026/go/risk"
	"gopkg.in/yaml.v3"
)

// The Go side of the contract check.
//
// The PHP side of this boundary is *generated* from contract/risk-engine.openapi.yaml, so it
// cannot drift: regenerate and the compiler and PHPStan object. The Go side is hand-written,
// which is nicer to read but means nothing stops it from silently disagreeing with the
// document. This test is what closes that gap.
//
// It is the reason "make contract-break" fails in both languages from a single edit. Without
// it, changing the contract would break PHP at analysis time and leave Go quietly serving the
// old shape until something noticed in production — which is precisely the failure mode the
// talk is about.

type openAPIDocument struct {
	Components struct {
		Schemas map[string]schemaNode `yaml:"schemas"`
	} `yaml:"components"`
}

type schemaNode struct {
	Type                 string                `yaml:"type"`
	Required             []string              `yaml:"required"`
	AdditionalProperties *bool                 `yaml:"additionalProperties"`
	Properties           map[string]schemaNode `yaml:"properties"`
	Format               string                `yaml:"format"`
	Nullable             bool                  `yaml:"nullable"`
	Enum                 []string              `yaml:"enum"`
	// any rather than a typed node: defaults in this document are ints and strings, and all
	// this test needs to know is whether one is present.
	Default any `yaml:"default"`
}

func loadContract(t *testing.T) openAPIDocument {
	t.Helper()

	path := filepath.Join("..", "..", "..", "contract", "risk-engine.openapi.yaml")

	raw, err := os.ReadFile(path)
	if err != nil {
		t.Fatalf("cannot read the contract at %s: %v", path, err)
	}

	var doc openAPIDocument
	if err := yaml.Unmarshal(raw, &doc); err != nil {
		t.Fatalf("contract is not valid YAML: %v", err)
	}

	return doc
}

// jsonFieldsOf maps a struct's JSON field names to their Go types.
func jsonFieldsOf(v any) map[string]reflect.Type {
	out := map[string]reflect.Type{}

	rt := reflect.TypeOf(v)
	for i := range rt.NumField() {
		field := rt.Field(i)

		name := strings.Split(field.Tag.Get("json"), ",")[0]
		if name == "" || name == "-" {
			continue
		}

		out[name] = field.Type
	}

	return out
}

func TestAssessRequestMatchesTheContract(t *testing.T) {
	schema := loadContract(t).Components.Schemas["AssessRequest"]
	if len(schema.Properties) == 0 {
		t.Fatal("contract declares no properties for AssessRequest")
	}

	assertFieldsMatch(t, "AssessRequest", schema, jsonFieldsOf(AssessRequest{}))
}

func TestAssessResponseMatchesTheContract(t *testing.T) {
	schema := loadContract(t).Components.Schemas["AssessResponse"]
	if len(schema.Properties) == 0 {
		t.Fatal("contract declares no properties for AssessResponse")
	}

	assertFieldsMatch(t, "AssessResponse", schema, jsonFieldsOf(AssessResponse{}))
}

func assertFieldsMatch(t *testing.T, name string, schema schemaNode, fields map[string]reflect.Type) {
	t.Helper()

	// Every documented property must exist on the struct.
	for property, node := range schema.Properties {
		goType, ok := fields[property]
		if !ok {
			t.Errorf("%s: the contract declares %q but the Go struct has no such json field",
				name, property)
			continue
		}

		assertTypeCompatible(t, name, property, node, goType)
	}

	// And the struct must not invent properties the contract does not describe. This is the
	// direction that catches "someone added a field to Go and forgot the document".
	for property := range fields {
		if _, ok := schema.Properties[property]; !ok {
			t.Errorf("%s: the Go struct exposes %q which the contract does not declare",
				name, property)
		}
	}
}

// assertTypeCompatible checks the Go type against the schema, including the pointer/optional
// correspondence.
//
// The pointer rule is the interesting one. A property that is neither required nor given a
// default is genuinely optional, and representing it as a bare int64 would make "absent" and
// "zero" the same value — so this test insists on a pointer for exactly those fields.
func assertTypeCompatible(t *testing.T, name, property string, node schemaNode, goType reflect.Type) {
	t.Helper()

	isRequired := false
	for _, r := range nodeRequired(node, name, property) {
		if r == property {
			isRequired = true
			break
		}
	}

	hasDefault := node.Default != nil
	mustBePointer := !isRequired && !hasDefault && !node.Nullable
	if node.Nullable {
		// A nullable property must be able to hold null.
		mustBePointer = true
	}

	isPointer := goType.Kind() == reflect.Ptr
	if mustBePointer && !isPointer {
		t.Errorf("%s.%s: contract makes this optional or nullable, so the Go field must be a "+
			"pointer to distinguish absent from zero (got %s)", name, property, goType)
	}

	underlying := goType
	if isPointer {
		underlying = goType.Elem()
	}

	switch node.Type {
	case "integer":
		if underlying.Kind() != reflect.Int64 {
			t.Errorf("%s.%s: contract says integer/%s, Go field is %s",
				name, property, node.Format, underlying)
		}
	case "string":
		if underlying.Kind() != reflect.String {
			t.Errorf("%s.%s: contract says string, Go field is %s", name, property, underlying)
		}
	case "boolean":
		if underlying.Kind() != reflect.Bool {
			t.Errorf("%s.%s: contract says boolean, Go field is %s", name, property, underlying)
		}
	}
}

// nodeRequired re-reads the parent schema's required list.
//
// Split out because schemaNode is flat and a property node does not know whether its parent
// listed it as required.
func nodeRequired(_ schemaNode, schemaName, _ string) []string {
	return requiredBySchema[schemaName]
}

// requiredBySchema mirrors the "required" lists in the contract.
//
// Deliberately restated here rather than threaded through the parser: if the contract's
// required list changes, this map must change too, and the mismatch shows up as a failing
// test rather than as a silently relaxed check.
var requiredBySchema = map[string][]string{
	"AssessRequest":  {"amountMinor", "currency", "country", "cardBin", "merchantId"},
	"AssessResponse": {"riskScore", "status", "decisionReason", "engine"},
}

func TestRequiredListsAgreeWithTheContract(t *testing.T) {
	doc := loadContract(t)

	for schemaName, expected := range requiredBySchema {
		schema, ok := doc.Components.Schemas[schemaName]
		if !ok {
			t.Fatalf("contract has no schema %q", schemaName)
		}

		if !equalUnordered(schema.Required, expected) {
			t.Errorf("%s: contract requires %v, this test expects %v — update requiredBySchema",
				schemaName, schema.Required, expected)
		}
	}
}

// TestContractForbidsUnknownProperties pins the schema decision that justifies
// DisallowUnknownFields in the handler. If the contract stopped saying
// additionalProperties: false, rejecting unknown fields at runtime would be stricter than the
// documented behaviour.
func TestContractForbidsUnknownProperties(t *testing.T) {
	doc := loadContract(t)

	for _, schemaName := range []string{"AssessRequest", "AssessResponse"} {
		schema := doc.Components.Schemas[schemaName]

		if schema.AdditionalProperties == nil || *schema.AdditionalProperties {
			t.Errorf("%s: expected additionalProperties: false, which is what allows the "+
				"handler to treat an unknown field as contract drift", schemaName)
		}
	}
}

// TestResponseEnumsCoverEveryReasonTheEngineCanProduce closes the last gap: the engine could
// return a reason code the contract never documents, and no amount of struct checking would
// notice because the Go type is just a string.
func TestResponseEnumsCoverEveryReasonTheEngineCanProduce(t *testing.T) {
	schema := loadContract(t).Components.Schemas["AssessResponse"]

	documented := map[string]bool{}
	for _, value := range schema.Properties["decisionReason"].Enum {
		documented[value] = true
	}

	// Every reason constant declared by the core.
	for _, reason := range risk.AllReasonCodes() {
		if !documented[reason] {
			t.Errorf("the engine can return decisionReason %q but the contract does not "+
				"document it", reason)
		}
	}

	documentedStatuses := map[string]bool{}
	for _, value := range schema.Properties["status"].Enum {
		documentedStatuses[value] = true
	}
	for _, status := range []string{"approved", "challenged", "declined"} {
		if !documentedStatuses[status] {
			t.Errorf("status %q is not documented in the contract", status)
		}
	}
}

func equalUnordered(a, b []string) bool {
	if len(a) != len(b) {
		return false
	}

	seen := map[string]int{}
	for _, v := range a {
		seen[v]++
	}
	for _, v := range b {
		seen[v]--
		if seen[v] < 0 {
			return false
		}
	}

	return true
}
