<?php

declare(strict_types=1);

namespace App\Enums;

enum ReviewAction: string
{
    case SUBMIT = 'submit';
    case AUTO_REJECT = 'auto_reject';
    case FIRST_PASS = 'first_pass';
    case FIRST_REJECT = 'first_reject';
    case MODIFY_REQUIRED = 'modify_required';
    case APPEAL = 'appeal';
    case SECOND_PASS = 'second_pass';
    case SECOND_REJECT = 'second_reject';

    public function label(): string
    {
        return match ($this) {
            self::SUBMIT => '提交审核',
            self::AUTO_REJECT => '机审驳回',
            self::FIRST_PASS => '一审通过',
            self::FIRST_REJECT => '一审驳回',
            self::MODIFY_REQUIRED => '需修改',
            self::APPEAL => '申诉',
            self::SECOND_PASS => '二审通过',
            self::SECOND_REJECT => '二审驳回',
        };
    }
}
