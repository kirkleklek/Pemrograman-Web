<?php

namespace Tests\Unit;

use Illuminate\Http\JsonResponse;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\AssertsPrivateResponse;

// These are controls for the assertion itself, not mocked application requests.
class JsonPrivacyAssertionTest extends TestCase
{
    use AssertsPrivateResponse;

    public function test_actual_error_contract_contains_no_private_target_values(): void
    {
        $response = TestResponse::fromBaseResponse(new JsonResponse([
            'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
        ], 403));

        $this->assertResponseExcludesSecrets($response, ['materials/privat-B.pdf']);
    }

    public function test_escaped_path_that_evades_raw_text_search_is_detected(): void
    {
        $path = 'materials/privat-B.pdf';
        $response = TestResponse::fromBaseResponse(new JsonResponse(['file_path' => $path], 403));
        // Reproduce M01: this old assertion passes even though the path is present.
        $response->assertDontSee($path, false);
        $this->assertSame($path, $response->json('file_path'));
        $this->expectException(AssertionFailedError::class);
        $this->assertResponseExcludesSecrets($response, [$path]);
    }

    public static function leakedPayloads(): array
    {
        return [
            'nested path' => [['data' => [['file_path' => 'submissions/privat-B.pdf']]], 'submissions/privat-B.pdf'],
            'path embedded in message' => [['message' => 'File: submissions/privat-B.pdf'], 'submissions/privat-B.pdf'],
            'path in key' => [['submissions/privat-B.pdf' => true], 'submissions/privat-B.pdf'],
            'unicode value' => [['data' => ['note' => 'Jawaban pribadi é']], 'Jawaban pribadi é'],
            'escaped quotes' => [['message' => 'Catatan "privat" B'], 'Catatan "privat" B'],
            'scalar JSON string' => ['File submissions/privat-B.pdf', 'submissions/privat-B.pdf'],
        ];
    }

    #[DataProvider('leakedPayloads')]
    public function test_leaks_in_decoded_keys_and_values_are_rejected(mixed $payload, string $secret): void
    {
        $response = TestResponse::fromBaseResponse(new JsonResponse($payload, 403));
        $this->expectException(AssertionFailedError::class);
        $this->assertResponseExcludesSecrets($response, [$secret]);
    }
}
