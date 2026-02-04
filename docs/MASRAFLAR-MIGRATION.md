# Masraflar Modülü - Veritabanı Migration

Masraflar (expenses) modülünü kullanabilmek için aşağıdaki SQL migration'ını çalıştırmanız gerekir.

## Migration Dosyası

`php-app/sql/migrations/add_expenses_and_credit_cards.sql`

## Çalıştırma

MySQL/MariaDB ile:

```bash
mysql -u root -p depotakip < php-app/sql/migrations/add_expenses_and_credit_cards.sql
```

Veya phpMyAdmin / Adminer üzerinden dosya içeriğini kopyalayıp çalıştırın.

## Not

- `bank_accounts` tablosuna `opening_balance` kolonu eklenir. Eğer bu kolon zaten varsa (önceki migration çalıştırıldıysa), migration dosyasındaki ALTER TABLE satırını yoruma alın.
- Yeni tablolar: `credit_cards`, `expense_categories`, `expenses`
