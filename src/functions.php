<?php
declare(strict_types=1);

namespace From;

function from(mixed $input): From
{
    return $input instanceof From ? $input : new From($input);
}
