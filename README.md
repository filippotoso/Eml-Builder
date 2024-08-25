# Eml Builder

A simple class to build EML files.

## Installing

Use Composer to install it:

```
composer require filippo-toso/eml-builder
```

## How does it work?

It's really easy:

```php
use FilippoToso\EmlBuilder\Address;
use FilippoToso\EmlBuilder\EmlBuilder;

$builder = (new EmlBuilder())
    ->from(Address::make('info@example.com', 'Acme Inc'))
    ->to(Address::make('info@example.com'))
    ->cc([Address::make('info@example.com'), Address::make('info@example.com', 'Acme Inc')])
    ->subject('Hello World!')
    ->text('This is the textual part of the email!')
    ->html('<h1>This is the HTML part of the email!</h1>');

// Get the EML content
$content = $builder->get();

// Save the EML content
$builder->save('test-email.eml');
```

That's it!