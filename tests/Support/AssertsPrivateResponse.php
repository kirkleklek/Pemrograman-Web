<?php

namespace Tests\Support;

use Illuminate\Testing\TestResponse;

trait AssertsPrivateResponse
{
    protected function assertResponseExcludesSecrets(TestResponse $response, array $secrets): void
    {
        if (str_contains(strtolower($response->headers->get('Content-Type', '')), 'json')) {
            $this->assertDecodedPayloadExcludesSecrets($response->json(), $secrets);

            return;
        }

        foreach ($secrets as $secret) {
            $response->assertDontSee($secret, false)->assertDontSee($secret);
        }
    }

    private function assertDecodedPayloadExcludesSecrets(mixed $payload, array $secrets): void
    {
        $strings = [];
        $pending = [$payload];
        while ($pending !== []) {
            $node = array_pop($pending);
            if (is_array($node)) {
                foreach ($node as $key => $value) {
                    $strings[] = (string) $key;
                    $pending[] = $value;
                }
            } elseif (is_string($node)) {
                $strings[] = $node;
            }
        }
        foreach ($secrets as $secret) {
            $found = false;
            foreach ($strings as $string) {
                if (str_contains($string, $secret)) {
                    $found = true;
                    break;
                }
            }
            $this->assertFalse($found, 'Private target data must not occur in decoded JSON keys or values.');
        }
    }
}
