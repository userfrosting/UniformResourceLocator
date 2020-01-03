<?php

/*
 * UserFrosting Uniform Resource Locator (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UniformResourceLocator
 * @copyright Copyright (c) 2013-2019 Alexander Weissman, Louis Charette
 * @license   https://github.com/userfrosting/UniformResourceLocator/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\UniformResourceLocator\Stream;

use RocketTheme\Toolbox\StreamWrapper\Stream as OriginalStream;

/**
 * {@inheritdoc}
 */
class Stream extends OriginalStream
{
    /**
     * Support for stream_set_option
     *  - stream_set_blocking()
     *  - stream_set_timeout()
     *  - stream_set_write_buffer().
     *
     * @param int $option
     * @param int $arg1
     * @param int $arg2
     *
     * @return bool
     *
     * @see http://php.net/manual/streamwrapper.stream-set-option.php
     */
    public function stream_set_option(int $option, int $arg1, int $arg2)
    {
        switch ($option) {
            case STREAM_OPTION_BLOCKING:
                return stream_set_blocking($this->handle, $arg1);
            case STREAM_OPTION_READ_TIMEOUT:
                return stream_set_timeout($this->handle, $arg1, $arg2);
            case STREAM_OPTION_WRITE_BUFFER:
                return stream_set_write_buffer($this->handle, $arg2);
            default:
                return false;
        }
    }
}
