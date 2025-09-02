# RideTech API
## Setup
1. Clone the repository: `git clone <repo-url>`
2. Install dependencies: `composer install`
3. Copy `.env.example` to `.env`
4. Configure database in `.env`
5. Run migrations: `php artisan migrate`
6. Start server: `php artisan serve`
7. View Swagger at `/api/documentation`

## Additional Features
- **Rate Limiting**: using `throttle:api`
- **Caching**: for review lists in `ReviewController`

## Notes
- Ensure MySQL/PostgreSQL is running
- Swagger documentation available at `/api/documentation`
- Run tests: `php artisan test`
