# Flash Sale API

## Assumptions and Invariants Enforced

### Stock Management
- **Available Stock Formula**: `available = stock - reserved - sold`
- **Stock Immutability**: Total `stock` is immutable once set (represents initial inventory)
- **Dynamic Counters**: `reserved` and `sold` counters track current state
- **Non-negative Stock**: Available stock cannot go below zero

### Hold System
- **Hold Duration**: Active holds expire after exactly 5 minutes
- **Hold States**: `active`, `used`, `expired`, `cancelled`
- **Idempotency**: Duplicate hold requests with same `idempotency_key` return existing active hold
- **Atomic Operations**: Hold creation and stock reservation happen in database transactions

### Concurrency Control
- **Row Locking**: Product rows are locked during hold creation to prevent race conditions
- **Transaction Isolation**: All stock operations use database transactions
- **Cache TTL**: Stock cache expires after 10 seconds to ensure freshness

### Business Rules
- **No Overselling**: System prevents selling more items than available stock
- **Hold Expiration**: Expired holds automatically release reserved stock
- **Background Processing**: Hold cleanup runs via queued jobs with distributed locks

## Running the Application

### Prerequisites
- PHP 8.2+
- Composer
- Node.js & npm (for frontend assets)
- Database (MySQL/PostgreSQL/SQLite)

### Installation

```bash
# Clone repo
git clone https://github.com/el3amed74/Flash-Sale.git

# Install PHP dependencies
composer install

# Copy environment file and configure
cp .env.example .env
php artisan key:generate


# Run database migrations
php artisan migrate

# For using seeder 
php artisan migrate --seed

```

### Development Server

```bash
# Start all services (server, queue worker)
composer run dev
```

This runs:
- Laravel development server on `http://localhost:8000`
- Queue worker for background job processing
- Vite dev server for frontend assets

### Alternative Commands

```bash
# Start only the Laravel server
php artisan serve

# Start only the queue worker
php artisan queue:work

```

### Testing

```bash
# Run all tests
composer run test

# Run specific test suite
php artisan test tests/Feature/OrderApiTest.php

```



## Logs and Metrics

### Application Logs
**Location**: `storage/logs/laravel.log`

**Key Log Events**:
- **Stock Operations**: Cache hits/misses, database queries
- **Hold Creation**: Success/failure with product_id, quantity, expiry
- **Hold Expiration**: Automatic stock release with hold_id, product_id
- **Concurrency Issues**: Race condition prevention, transaction failures
- **Error Handling**: Failed operations with stack traces


### Key Metrics to Monitor

1. **Hold Success Rate**: Ratio of successful vs failed hold requests
2. **Queue Processing**: Job throughput and failure rates
3. **Stock Cache Hit Rate**: Cache effectiveness vs database load
4. **Transaction Conflicts**: Database lock wait times
5. **Hold Expiration Rate**: Automatic cleanup effectiveness



## API Endpoints

See `docs/flash-sale.postman_collection.json` for complete API documentation.

**Core Endpoints**:
- `GET /api/products/{id}` - Get product with available stock
- `POST /api/holds` - Create stock reservation (hold)
- `POST /api/orders` - Convert hold to order
- `POST /api/payments/webhook` - Payment confirmation webhook


