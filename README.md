# AmoCRM PHP API 2020

PHP-класс для работы с AmoCRM через [упрощенную авторизацию](#упрощенная-авторизация-amocrm).

## Что умеет?

- Авторизация в AmoCRM от имени владельца аккаунта
- Отправлять любые запросы из документации AmoCRM 

## Упрощенная авторизация AmoCRM

В 2020-м (или в 2019?) AmoCRM убрала возможность возможность создать ключ API в личном кабинете.

С тех пор, все новые интеграции должны проходить авторизацию через oAuth2, что кажется немного избыточным, если, например, нужно только отправлять контакты и заявки с сайта в AmoCRM.

Однако, даже [упрощенная авторизация](https://www.amocrm.ru/developers/content/oauth/easy-auth) в AmoCRM требует получать новый `access_token` каждые 24 часа, через `refresh_token`, который тоже обновляется с получением нового `access_token`

Как раньше не получится, когда можно было взять ключ API и спокойно отправлять нужную информацию. 

## Как использовать?

#### Установка

Самый простой вариант — установить через Composer `composer require vikdiesel/amocrm-php-wrapper`

#### Создание интеграции

Перейдите в раздел *Настройки -> Интеграции* и щелкните *Создать Интеграцию*

![Laravel Bulma Authentication Preset](https://marketto.ru/images/amocrm-1.png)

После сохранения вы сможете получить нужные ключи.

![Laravel Bulma Authentication Preset](https://marketto.ru/images/amocrm-2.png)

#### Простой пример

<pre>
use AmoCrmPhpWrapper\Package\AmoClient;

// Домен в AmoCRM
$amo_domain = 'YOURNAME.amocrm.ru';

// ID интеграции
$client_id = '852d137c-e258-4f18-9db7-aaaaaaaaaaaa';

// Секретный ключ
$client_secret = '';

// Обязательно должен быть точно такой же, какой был указан при создании интеграции в интерфейсе AmoCRM
$redirect_uri = 'https://example.com';

// Код авторизации (действует 20 минут). В течение этого времени необходимо сделать первый запрос. Если прошло больше времени, то закройте и откройте карточку интеграции заново в интерфейсе amoCRM
$initial_code = '';

try {
  $amoClient = new AmoClient( $amo_domain, $client_id, $client_secret, $redirect_uri, $initial_code );

  $name  = 'Lubjek Strowinski';
  $phone = '+447824200245';
  $sale  = '7777';

  $r = $amoClient->request( '/api/v2/contacts', [
    'add' => [
      [
        'name'          => $name,
        'tags'          => 'test-case',
        'custom_fields' => [
          [
            'id'     => '406896',
            'values' => [
              [
                'value' => $phone,
                'enum'  => 'WORK'
              ]
            ]
          ]
        ]
      ]
    ]
  ] );

  $r = $amoClient->request( '/api/v2/leads', [
    'add' => [
      [
        'name'        => $name,
        'tags'        => 'test-case',
        'sale'        => $sale,
        'contacts_id' => $r->_embedded->items[0]->id
      ]
    ]
  ] );

} catch ( \AmoCrmPhpWrapper\Package\Exception\AmoClientException $exception ) {
  echo $exception->getMessage();
}
</pre>
