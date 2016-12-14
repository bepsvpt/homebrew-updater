# Homebrew Updater

[![StyleCI](https://styleci.io/repos/76054785/shield)](https://styleci.io/repos/76054785)

Automatically check release of homebrew formulas.

## Requirements

- https://laravel.com/docs/5.3#server-requirements
- SQLite

## Installation

1. git clone https://github.com/BePsvPT/homebrew-updater.git
2. touch database/database.sqlite
3. cp .env.example .env
4. composer install --no-dev -o
5. php artisan key:generate
6. php artisan migrate --force
7. set up crontab: https://laravel.com/docs/scheduling#introduction

## Artisan Commands

- formula:manage
- formula:check
