# Solcambio

API REST de conversión CLP ↔ USD en Laravel 13. Tasa fija: **1 USD = 950 CLP**. Sin base de datos ni APIs externas.

## Requisitos

- PHP 8.3+
- Composer
- Node.js + npm

## Instalación

```bash
git clone <url-del-repo> solcambio
cd solcambio
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run dev
php artisan serve
```

Abrir: `http://127.0.0.1:8000`

## Scripts

| Comando | Para qué |
|---|---|
| `php artisan serve` | Levanta la API y la interfaz |
| `npm run dev` | Compila CSS/JS en desarrollo |
| `npm run build` | Compila CSS/JS para producción |
| `php artisan test` | Corre las pruebas |
| `php artisan route:list --path=api` | Lista las rutas de la API |

## Estructura

| Archivo | Responsabilidad |
|---|---|
| `routes/api.php` | Rutas de la API |
| `app/Http/Controllers/CurrencyController.php` | Valida y responde JSON |
| `app/Services/CurrencyConverterService.php` | Tasa y cálculos de conversión |
| `bootstrap/app.php` | Formato JSON de errores 422, 404 y 405 |
| `tests/Feature/CurrencyApiTest.php` | Pruebas de los endpoints |
| `postman/Solcambio.postman_collection.json` | Colección para Postman / Thunder Client |
| `routes/web.php` | Ruta de la interfaz |
| `resources/views/solcambio.blade.php` | Interfaz |
| `resources/js/solcambio.js` | Lógica de la interfaz (fetch, historial, tema) |

## Endpoints

Headers en todas las solicitudes:

```
Content-Type: application/json
Accept: application/json
```

### GET `/api/exchange-rate`

200:

```json
{ "success": true, "from": "CLP", "to": "USD", "exchange_rate": 950 }
```

### POST `/api/convert` (CLP → USD)

Solicitud:

```json
{ "amount": 19000 }
```

200:

```json
{ "success": true, "amount_clp": 19000, "amount_usd": 20, "exchange_rate": 950 }
```

422:

```json
{ "success": false, "message": "El monto debe ser mayor a cero." }
```

| Regla | Mensaje |
|---|---|
| `required` | El campo amount es obligatorio. |
| `numeric` | El monto debe ser numérico. |
| `integer` | El monto debe ser un número entero de pesos chilenos. |
| `min:1` | El monto debe ser mayor a cero. |
| `max:100000000` | El monto no puede superar los $100.000.000 CLP. |

### POST `/api/convert/usd-to-clp` (USD → CLP)

Solicitud:

```json
{ "amount": 20 }
```

200:

```json
{ "success": true, "amount_usd": 20, "amount_clp": 19000, "exchange_rate": 950 }
```

422:

```json
{ "success": false, "message": "El monto en dólares admite como máximo dos decimales." }
```

| Regla | Mensaje |
|---|---|
| `required` | El campo amount es obligatorio. |
| `numeric` | El monto debe ser numérico. |
| `decimal:0,2` | El monto en dólares admite como máximo dos decimales. |
| `min:0.01` | El monto debe ser mayor a cero. |
| `max:105263.15` | El monto no puede superar los US$ 105.263,15 (equivalente a $100.000.000 CLP). |

### Otros errores

| Caso | HTTP | Respuesta |
|---|---|---|
| Ruta inexistente | 404 | `{ "success": false, "message": "La ruta solicitada no existe." }` |
| Método incorrecto | 405 | `{ "success": false, "message": "Método HTTP no permitido para esta ruta." }` |

## Redondeo

- CLP → USD: 2 decimales.
- USD → CLP: pesos enteros.

## Casos de prueba obligatorios

| Monto | Resultado | HTTP |
|---|---|---|
| 19000 | 20 USD | 200 |
| 1000 | 1,05 USD | 200 |
| 950 | 1 USD | 200 |
| 0 | Error de validación | 422 |
| -5000 | Error de validación | 422 |
| "hola" | Error de validación | 422 |
| 1500.50 | Error de validación | 422 |
| 100000001 | Error de validación | 422 |
| Campo ausente | Error de validación | 422 |

```bash
php artisan test
```

Postman / Thunder Client: importar `postman/Solcambio.postman_collection.json` y ejecutar la colección.

## curl

```bash
curl http://127.0.0.1:8000/api/exchange-rate
```

```bash
curl -X POST http://127.0.0.1:8000/api/convert -H "Content-Type: application/json" -H "Accept: application/json" -d '{"amount": 19000}'
```

```bash
curl -X POST http://127.0.0.1:8000/api/convert -H "Content-Type: application/json" -H "Accept: application/json" -d '{"amount": "hola"}'
```

```bash
curl -X POST http://127.0.0.1:8000/api/convert/usd-to-clp -H "Content-Type: application/json" -H "Accept: application/json" -d '{"amount": 20}'
```

## Interfaz

- Conversión CLP → USD y USD → CLP con botón Invertir.
- Montos rápidos.
- Historial de las últimas 5 conversiones.
- Modo claro / oscuro.
- Panel con la respuesta JSON cruda.
- La interfaz no valida: todo lo valida la API.

- Jomni Ruiz
