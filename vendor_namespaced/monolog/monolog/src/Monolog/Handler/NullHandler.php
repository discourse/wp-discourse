<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WPDiscourse\Monolog\Handler;

use WPDiscourse\Monolog\Logger;
/**
 * Blackhole
 *
 * Any record it can handle will be thrown away. This can be used
 * to put on top of an existing stack to override it temporarily.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class NullHandler extends \WPDiscourse\Monolog\Handler\AbstractHandler
{
    /**
     * @param int $level The minimum logging level at which this handler will be triggered
     */
    public function __construct($level = \WPDiscourse\Monolog\Logger::DEBUG)
    {
        parent::__construct($level, \false);
    }
    /**
     * {@inheritdoc}
     */
    public function handle(array $record)
    {
        if ($record['level'] < $this->level) {
            return \false;
        }
        return \true;
    }
}
