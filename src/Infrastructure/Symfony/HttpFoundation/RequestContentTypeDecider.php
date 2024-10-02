<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\HttpFoundation;

use Symfony\Component\HttpFoundation\Request;

final class RequestContentTypeDecider
{
    private function __construct() {}

    public static function getContentType(Request $request): ?RequestContentType
    {
        $mimeType = $request->headers->get('CONTENT-TYPE', null);
        if (null === $mimeType || '' === $mimeType) {
            return null;
        }

        $canonicalMimeType = $mimeType;
        $charset = null;
        if (false !== ($pos = strpos($mimeType, ';'))) {
            $canonicalMimeType = trim(substr($mimeType, 0, $pos));
            if ('' === $canonicalMimeType) {
                return null;
            }

            $options = str_replace('"', '', strtolower(trim(substr($mimeType, $pos + 1))));
            if (str_starts_with($options, 'charset=') && false !== ($pos = strpos($options, '='))) {
                $charset = trim(substr($options, $pos + 1));
                if ('' === $charset) {
                    $charset = null;
                }
            }
        }

        return new RequestContentType($canonicalMimeType, $charset);
    }

    /**
     * @return ($fallback is null ? RequestContentTypeEnum|null : RequestContentTypeEnum)
     */
    public static function decideContentType(Request $request, ?RequestContentTypeEnum $fallback): ?RequestContentTypeEnum
    {
        $contentTypes = $request->getAcceptableContentTypes();
        if ([] === $contentTypes) {
            return $fallback;
        }

        foreach ($contentTypes as $contentType) {
            $type = RequestContentTypeEnum::tryFrom($contentType);
            if (null === $type) {
                continue;
            }

            return $type;
        }

        return $fallback;
    }
}
