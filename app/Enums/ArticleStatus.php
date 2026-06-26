<?php

declare(strict_types=1);

namespace App\Enums;

enum ArticleStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case FIRST_PASS = 'first_pass';
    case PUBLISHED = 'published';
    case FIRST_REJECT = 'first_reject';
    case MODIFY_REQUIRED = 'modify_required';
    case APPEALING = 'appealing';
    case SECOND_PASS = 'second_pass';
    case SECOND_REJECT = 'second_reject';
    case UNLISTED = 'unlisted';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => '草稿',
            self::PENDING => '待审核',
            self::FIRST_PASS => '一审通过',
            self::PUBLISHED => '已发布',
            self::FIRST_REJECT => '一审驳回',
            self::MODIFY_REQUIRED => '需修改',
            self::APPEALING => '申诉中',
            self::SECOND_PASS => '二审通过',
            self::SECOND_REJECT => '二审驳回',
            self::UNLISTED => '已下架',
        };
    }

    public function isPublished(): bool
    {
        return in_array($this, [self::PUBLISHED, self::FIRST_PASS, self::SECOND_PASS]);
    }

    public function isRejected(): bool
    {
        return in_array($this, [self::FIRST_REJECT, self::SECOND_REJECT]);
    }

    public function isPending(): bool
    {
        return $this === self::PENDING || $this === self::APPEALING;
    }

    public function isDraft(): bool
    {
        return $this === self::DRAFT;
    }

    public function isModifyRequired(): bool
    {
        return $this === self::MODIFY_REQUIRED;
    }
}
