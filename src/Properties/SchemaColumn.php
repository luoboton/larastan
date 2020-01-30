<?php

declare(strict_types=1);

/**
 * This file is part of Larastan.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace NunoMaduro\Larastan\Properties;

final class SchemaColumn
{
    /** @var string */
    public $name;

    /** @var string */
    public $readableType;

    /** @var string */
    public $writeableType;

    /** @var bool */
    public $nullable;

    /** @var ?array<int, string> */
    public $options;

    /**
     * @param string        $name
     * @param string        $readableType
     * @param bool          $nullable
     * @param string[]|null $options
     */
    public function __construct(
        string $name,
        string $readableType,
        bool $nullable = false,
        ?array $options = null
    ) {
        $this->name = $name;
        $this->readableType = $readableType;
        $this->writeableType = $readableType;
        $this->nullable = $nullable;
        $this->options = $options;
    }
}
