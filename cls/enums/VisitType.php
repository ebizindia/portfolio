<?php
namespace eBizIndia\enums;

/**
 * VisitType enum for visit reports
 * File: VisitType.php
 */
enum VisitType: int {
    case NEW = 1;
    case EXISTING = 2;
    
    public function label(): string {
        return match($this) {
            self::NEW => 'New',
            self::EXISTING => 'Existing',
        };
    }
    
    public static function getOptions(): array {
        return [
            self::NEW->value => self::NEW->label(),
            self::EXISTING->value => self::EXISTING->label(),
        ];
    }
    
    public static function fromValue(int $value): ?self {
        return match($value) {
            1 => self::NEW,
            2 => self::EXISTING,
            default => null,
        };
    }
}