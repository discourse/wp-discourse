<?php
require_once WPDISCOURSE_PATH . '/vendor_namespaced/monolog/monolog/src/Monolog/Formatter/FormatterInterface.php';
require_once WPDISCOURSE_PATH . '/vendor_namespaced/monolog/monolog/src/Monolog/Formatter/NormalizerFormatter.php';
require_once WPDISCOURSE_PATH . '/vendor_namespaced/monolog/monolog/src/Monolog/Formatter/LineFormatter.php';
require_once WPDISCOURSE_PATH . '/vendor_namespaced/monolog/monolog/src/Monolog/Handler/HandlerInterface.php';
require_once WPDISCOURSE_PATH . '/vendor_namespaced/monolog/monolog/src/Monolog/ResettableInterface.php';
require_once WPDISCOURSE_PATH . '/vendor_namespaced/monolog/monolog/src/Monolog/Handler/AbstractHandler.php';
require_once WPDISCOURSE_PATH . '/vendor_namespaced/monolog/monolog/src/Monolog/Handler/AbstractProcessingHandler.php';
require_once WPDISCOURSE_PATH . '/vendor_namespaced/monolog/monolog/src/Monolog/Handler/NullHandler.php';
require_once WPDISCOURSE_PATH . '/vendor_namespaced/monolog/monolog/src/Monolog/Handler/StreamHandler.php';
require_once WPDISCOURSE_PATH . '/vendor_namespaced/psr/log/Psr/Log/LoggerInterface.php';
require_once WPDISCOURSE_PATH . '/vendor_namespaced/monolog/monolog/src/Monolog/Logger.php';
require_once WPDISCOURSE_PATH . '/vendor_namespaced/monolog/monolog/src/Monolog/Utils.php';
require_once WPDISCOURSE_PATH . '/vendor_namespaced/psr/log/Psr/Log/AbstractLogger.php';
require_once WPDISCOURSE_PATH . '/vendor_namespaced/psr/log/Psr/Log/InvalidArgumentException.php';
require_once WPDISCOURSE_PATH . '/vendor_namespaced/psr/log/Psr/Log/LogLevel.php';
require_once WPDISCOURSE_PATH . '/vendor_namespaced/psr/log/Psr/Log/LoggerAwareInterface.php';
require_once WPDISCOURSE_PATH . '/vendor_namespaced/psr/log/Psr/Log/LoggerAwareTrait.php';
require_once WPDISCOURSE_PATH . '/vendor_namespaced/psr/log/Psr/Log/LoggerTrait.php';
require_once WPDISCOURSE_PATH . '/vendor_namespaced/psr/log/Psr/Log/NullLogger.php';