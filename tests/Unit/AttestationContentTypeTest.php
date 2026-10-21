<?php

namespace Tests\Unit;

use App\Services\ExternalService;
use PHPUnit\Framework\TestCase;

class AttestationContentTypeTest extends TestCase
{
    public function test_sniffs_jpeg_even_when_declared_as_pdf(): void
    {
        $jpeg = "\xFF\xD8\xFF\xE0".str_repeat("\x00", 16);

        $this->assertSame(
            'image/jpeg',
            ExternalService::detectDocumentContentType($jpeg, 'application/pdf')
        );
    }

    public function test_sniffs_png(): void
    {
        $png = "\x89PNG\r\n\x1A\n".str_repeat("\x00", 8);

        $this->assertSame('image/png', ExternalService::detectDocumentContentType($png, 'application/pdf'));
    }

    public function test_sniffs_pdf(): void
    {
        $this->assertSame(
            'application/pdf',
            ExternalService::detectDocumentContentType('%PDF-1.4 fake', 'image/jpeg')
        );
    }

    public function test_does_not_trust_declared_pdf_without_magic_bytes(): void
    {
        $this->assertSame(
            'application/octet-stream',
            ExternalService::detectDocumentContentType('{"message":"error"}', 'application/pdf')
        );
    }
}
