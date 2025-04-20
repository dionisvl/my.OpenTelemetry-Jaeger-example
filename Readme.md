# OpenTelemetry + Jaeger: Golang/PHP Demo 

It is a simple demo to show how to use OpenTelemetry with Jaeger in Golang and PHP  
that generates traces spans and sends them to Jaeger.  


## Installation
- `docker compose up --build`

## PHP
- http://localhost:8081/?n=4
![img_3.png](imgs/img_3.png)


## Golang
- http://localhost:8080/fib?n=4
![img.png](imgs/img.png)
- ![img_1.png](imgs/img_1.png)
- ![img_2.png](imgs/img_2.png)

## Jaeger
- http://localhost:16686
