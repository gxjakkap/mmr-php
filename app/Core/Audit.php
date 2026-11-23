<?php
declare(strict_types=1);
namespace App\Core;

final class Audit
{
    public static function log(Db $db, ?string $actorId, string $action, string $entity, ?string $entityId, array $detail = []): void
    {
        $db->insert('audit_logs', [
            'id' => Db::uuid(), 'actor_id' => $actorId, 'action' => $action, 'entity_type' => $entity,
            'entity_id' => $entityId, 'detail' => json_encode($detail ?: new \stdClass(), JSON_UNESCAPED_UNICODE),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}
