<?php

function template_xpath($html, $values) {

  if (!is_noble($html)) {
    throw new Exception('Invalid Template HTML.');
  }

  $dom = new DomDocument();
  $dom->loadHTML('<' . '?xml encoding="UTF-8">' . $html);

  $xpath = new DOMXPath($dom);

  foreach ($values as $query => $attributes) {

    if (!is_noble($query)) {
      throw new Exception('Invalid Template XPath.');
    }

    foreach ($xpath->query($query) as $element) {
      foreach ($attributes as $attribute => $value) {

        if (!is_noble($attribute)) {
          throw new Exception('Invalid Template Attribute.');
        }

        if ($attribute) {
          $safe = false;
          if ($attribute == 'href') {
            if (preg_match('/^https?:\/\//', $value)) {
              $safe = true; // Not "javascript:..."
            }
          } else if ($attribute == 'class') {
            if (in_array($value, ['admin', 'important'])) {
              $safe = true; // Only allow specific classes?
            }
          } else if (preg_match('/^data-[a-z]+$/', $attribute)) {
            if (preg_match('/^[a-z0-9 ]+$/i', $value)) {
              $safe = true;
            }
          }
          if ($safe) {
            $element->setAttribute($attribute, $value);
          }
        } else {
          $element->textContent = $value;
        }

      }
    }

  }

  $html = '';
  $body = $dom->documentElement->firstChild;
  if ($body->hasChildNodes()) {
    foreach ($body->childNodes as $node) {
      $html .= $dom->saveXML($node);
    }
  }

  return $html;

}

$template_html = '
  <p>Hello <span id="username"></span></p>
  <p><a>Website</a></p>';

echo template_xpath($template_html, [
    '//span[@id="username"]' => [
        NULL      => 'Name', // The textContent
        'class'   => 'admin',
        'data-id' => '123',
      ],
    '//a' => [
        'href' => 'https://example.com',
      ],
  ]);

?>