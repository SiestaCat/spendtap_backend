#!/bin/bash
php bin/console doctrine:migrations:migrate --no-interaction || true && apache2-foreground