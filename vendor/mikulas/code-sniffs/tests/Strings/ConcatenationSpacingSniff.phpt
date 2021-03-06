<?php

require __DIR__ . '/../bootstrap.php';

use Tester\Assert;

$sniff = 'cs.Strings.ConcatenationSpacing';

$cs = runPhpcs($sniff, <<<CONTENT
<?php
'foo'.'bar'
CONTENT
);

Assert::same(array('MissingBefore', 'MissingAfter'), $cs->rules);


$cs = runPhpcs($sniff, <<<CONTENT
<?php
'foo'  .  'bar'
CONTENT
);

Assert::same(array('TooManyBefore', 'TooManyAfter'), $cs->rules);
