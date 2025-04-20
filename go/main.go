package main

import (
	"context"
	"fmt"
	"log"
	"net/http"
	"strconv"

	"go.opentelemetry.io/otel"
	"go.opentelemetry.io/otel/attribute"
	"go.opentelemetry.io/otel/exporters/otlp/otlptrace/otlptracehttp"
	"go.opentelemetry.io/otel/propagation"
	"go.opentelemetry.io/otel/sdk/resource"
	sdktrace "go.opentelemetry.io/otel/sdk/trace"
	"go.opentelemetry.io/otel/trace"
)

const (
	jaegerEndpoint = "jaeger:4318"
	serviceName    = "fibonacci-go"
)

func Fibonacci(ctx context.Context, n int) (ch chan int) {
	ch = make(chan int)
	go func() {
		tr := otel.GetTracerProvider().Tracer(serviceName)
		ctx, sp := tr.Start(ctx, fmt.Sprintf("Fibonacci(%d)", n))
		defer sp.End()

		result := 1
		if n > 1 {
			a := <-Fibonacci(ctx, n-1)
			b := <-Fibonacci(ctx, n-2)
			result = a + b
		}
		sp.SetAttributes(attribute.Int("n", n), attribute.Int("result", result))
		ch <- result
	}()
	return ch
}

func fibHandler(w http.ResponseWriter, req *http.Request) (n int) {
	if len(req.URL.Query()["n"]) != 1 {
		http.Error(w, "wrong number of arguments", 400)
		return
	}
	var err error
	n, err = strconv.Atoi(req.URL.Query()["n"][0])
	if err != nil {
		http.Error(w, "couldn't parse fib 'n'", 400)
		return
	}

	ctx := req.Context()
	result := <-Fibonacci(ctx, n)

	if sp := trace.SpanFromContext(ctx); sp != nil {
		sp.SetAttributes(attribute.Int("parameter", n), attribute.Int("result", result))
	}

	fmt.Fprintf(w, "%d\n", result)
	return
}

func main() {
	exporter, err := otlptracehttp.New(
		context.Background(),
		otlptracehttp.WithEndpoint(jaegerEndpoint),
		otlptracehttp.WithInsecure(),
	)
	if err != nil {
		log.Fatal(err)
	}

	tp := sdktrace.NewTracerProvider(
		sdktrace.WithSpanProcessor(sdktrace.NewBatchSpanProcessor(exporter)),
		sdktrace.WithResource(resource.NewWithAttributes(
			"",
			attribute.String("service.name", serviceName),
		)),
	)
	defer tp.Shutdown(context.Background())

	otel.SetTracerProvider(tp)
	otel.SetTextMapPropagator(propagation.TraceContext{})

	http.HandleFunc("/fib", func(w http.ResponseWriter, r *http.Request) {
		fibHandler(w, r)
	})

	log.Fatal(http.ListenAndServe(":8080", nil))
}
