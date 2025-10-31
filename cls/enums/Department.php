<?php
namespace eBizIndia\enums;

/**
 * Department enum for visit reports
 * File: Department.php
 */
enum Department: int {
    case SUPPLY_CHAIN = 1;
    case RND = 2;
    case OTHERS = 3;
    
    public function label(): string {
        return match($this) {
            self::SUPPLY_CHAIN => 'Supply Chain',
            self::RND => 'R & D',
            self::OTHERS => 'Others',
        };
    }
    
    public static function getOptions(): array {
        return [
            self::SUPPLY_CHAIN->value => self::SUPPLY_CHAIN->label(),
            self::RND->value => self::RND->label(),
            self::OTHERS->value => self::OTHERS->label(),
        ];
    }
    
    public static function fromValue(int $value): ?self {
        return match($value) {
            1 => self::SUPPLY_CHAIN,
            2 => self::RND,
            3 => self::OTHERS,
            default => null,
        };
    }
}