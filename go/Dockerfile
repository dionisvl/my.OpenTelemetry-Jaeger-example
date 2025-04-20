FROM golang:1.22-alpine AS builder

WORKDIR /app

COPY go.mod go.sum ./
RUN go mod tidy

COPY . .
RUN CGO_ENABLED=0 go build -o main .

FROM scratch

WORKDIR /app

COPY --from=builder /app/main .
EXPOSE 8080

CMD ["/app/main"]