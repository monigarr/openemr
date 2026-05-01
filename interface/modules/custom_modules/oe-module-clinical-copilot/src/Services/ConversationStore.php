<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * Session-bound Co-Pilot conversation (no chart JSON persisted; messages capped).
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class ConversationStore
{
    private const SESSION_KEY = 'clinical_copilot_conversation_v1';

    public const MAX_MESSAGES = 24;

    public function __construct(private readonly SessionInterface $session)
    {
    }

    /**
     * @return array{auth_user?:string,pid?:int,conversation_token?:string,messages?:list<array{role:string,content:string}>}
     */
    private function readBag(): array
    {
        $v = $this->session->get(self::SESSION_KEY);
        return is_array($v) ? $v : [];
    }

    /**
     * @param array{auth_user?:string,pid?:int,conversation_token?:string,messages?:list<array{role:string,content:string}>} $bag
     */
    private function writeBag(array $bag): void
    {
        $this->session->set(self::SESSION_KEY, $bag);
    }

    public function reset(): void
    {
        $this->session->remove(self::SESSION_KEY);
    }

    public function getOrCreateToken(string $authUser, int $pid): string
    {
        $bag = $this->readBag();
        if (($bag['auth_user'] ?? '') !== $authUser || (int) ($bag['pid'] ?? 0) !== $pid) {
            $bag = [
                'auth_user' => $authUser,
                'pid' => $pid,
                'conversation_token' => bin2hex(random_bytes(16)),
                'messages' => [],
            ];
            $this->writeBag($bag);
        }
        return (string) ($bag['conversation_token'] ?? '');
    }

    public function validate(string $authUser, int $pid, ?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }
        $bag = $this->readBag();
        return ($bag['conversation_token'] ?? '') === $token
            && ($bag['auth_user'] ?? '') === $authUser
            && (int) ($bag['pid'] ?? 0) === $pid;
    }

    public function appendMessage(string $role, string $content): void
    {
        $bag = $this->readBag();
        $messages = $bag['messages'] ?? [];
        if (!is_array($messages)) {
            $messages = [];
        }
        $messages[] = ['role' => $role, 'content' => $content];
        if (count($messages) > self::MAX_MESSAGES) {
            $messages = array_values(array_slice($messages, -self::MAX_MESSAGES));
        }
        $bag['messages'] = $messages;
        $this->writeBag($bag);
    }

    /**
     * @return list<array{role:string,content:string}>
     */
    public function getMessages(): array
    {
        $bag = $this->readBag();
        $m = $bag['messages'] ?? [];
        if (!is_array($m)) {
            return [];
        }
        $out = [];
        foreach ($m as $row) {
            if (!is_array($row)) {
                continue;
            }
            $r = isset($row['role']) && is_string($row['role']) ? $row['role'] : '';
            $c = isset($row['content']) && is_string($row['content']) ? $row['content'] : '';
            if ($r !== '' && $c !== '') {
                $out[] = ['role' => $r, 'content' => $c];
            }
        }
        return $out;
    }

    public function getConversationToken(): ?string
    {
        $bag = $this->readBag();
        $t = $bag['conversation_token'] ?? null;
        return is_string($t) && $t !== '' ? $t : null;
    }
}
