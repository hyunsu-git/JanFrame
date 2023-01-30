<?php
/**
 * This file is part of jan-frame.
 *
 * Licensed under The MIT License
 *
 * @author    hyunsu<hyunsu@foxmail.com>
 * @link      http://sun.hyunsu.cn
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 * @version   1.0
 *
 * ============================= 重大版本修正历史记录 ==========================
 * 版 本:          修改时间:          修改人:
 * 修改内容:
 *      //
 */

namespace jan\basic;

/**
 * ExitException represents the normal termination of the application.
 * You can manually throw this error to end the program normally
 */
class ExitException extends Exception
{
    /**
     * @var int|mixed the exit status. Exit codes must be in the range 0 to 254.
     */
    public $statusCode;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Exit';
    }

    public function __construct($status = 0, $message = null, $code = 0, \Exception $previous = null)
    {
        $this->statusCode = $status;
        parent::__construct($message, $code, $previous);
    }
}