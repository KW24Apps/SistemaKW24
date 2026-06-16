<?php
class SyncLock {
    private const LOCK_FILE = '/tmp/financeiro_sync.lock';
    private static $fh = null;

    public static function acquire(): bool {
        self::$fh = fopen(self::LOCK_FILE, 'c');
        if (!self::$fh) return false;
        return (bool) flock(self::$fh, LOCK_EX | LOCK_NB);
    }

    public static function release(): void {
        if (self::$fh) {
            flock(self::$fh, LOCK_UN);
            fclose(self::$fh);
            self::$fh = null;
        }
    }
}
