# Интеграция с Robokassa

## Структура файлов

```
api/
├── config.php              # Конфигурация (логин, пароли)
├── Robokassa.php           # Класс для работы с Robokassa
├── README.md               # Эта документация
└── payment/
    ├── create.php          # Создание платежа
    ├── result.php          # ResultURL - уведомление об оплате
    └── status.php          # Проверка статуса платежа
```

## Настройка

### 1. Получите данные в личном кабинете Robokassa

1. Зарегистрируйтесь на https://partner.robokassa.ru/
2. Создайте магазин
3. Получите:
   - **MerchantLogin** - идентификатор магазина
   - **Password #1** - для формирования подписи запроса
   - **Password #2** - для проверки подписи ответа

### 2. Настройте config.php

```php
return [
    'merchant_login' => 'ваш_логин',
    'password1' => 'пароль_1',
    'password2' => 'пароль_2',
    'is_test' => true,  // false для боевого режима
    // ...
];
```

### 3. Настройте URL в личном кабинете Robokassa

В настройках магазина укажите:

- **Result URL**: `https://yourdomain.com/api/payment/result.php`
- **Success URL**: `https://yourdomain.com/payment-success.html`
- **Fail URL**: `https://yourdomain.com/payment-fail.html`

Метод отправки: **POST** для Result URL

## API Endpoints

### POST /api/payment/create.php

Создание платежа и получение URL для оплаты.

**Запрос:**
```json
{
    "amount": 42000,
    "email": "user@example.com",
    "description": "Интенсив EMDR Express"
}
```

**Ответ:**
```json
{
    "success": true,
    "payment_url": "https://auth.robokassa.ru/Merchant/Index.aspx?...",
    "inv_id": 1234567890,
    "amount": 42000
}
```

### POST /api/payment/result.php

Обработка уведомления от Robokassa (вызывается автоматически).

**Параметры от Robokassa:**
- `OutSum` - сумма платежа
- `InvId` - номер заказа
- `SignatureValue` - подпись

**Ответ:** `OK{InvId}` при успехе

### GET /api/payment/status.php

Проверка статуса платежа.

**Запрос:**
```
GET /api/payment/status.php?inv_id=1234567890
```

**Ответ:**
```json
{
    "success": true,
    "code": 100,
    "status": "Операция выполнена успешно",
    "is_paid": true,
    "is_pending": false,
    "is_refunded": false
}
```

## Коды статусов платежа

| Код | Описание |
|-----|----------|
| 0 | Операция не найдена |
| 5 | Только инициирована, деньги не получены |
| 10 | Деньги не были получены |
| 50 | Деньги получены, ожидает подтверждения |
| 60 | Деньги возвращены покупателю |
| 80 | Исполнение приостановлено |
| 100 | Операция выполнена успешно |

## Тестирование

1. Установите `is_test => true` в config.php
2. Используйте тестовые карты Robokassa:
   - Номер: `4111 1111 1111 1111`
   - Срок: любой в будущем
   - CVV: любые 3 цифры

## Безопасность

- Храните `config.php` вне публичной директории или защитите через `.htaccess`
- Используйте HTTPS
- Проверяйте подписи всех запросов от Robokassa
- Логируйте все операции

## Документация Robokassa

- Основная: https://docs.robokassa.ru/
- Тестирование: https://docs.robokassa.ru/test-mode/
- XML интерфейс: https://docs.robokassa.ru/xml-interfaces/
