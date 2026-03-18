<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateJsonApiMediaType
{
    use ApiResponse;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedMediaType = config('jsonapi.media_type');
        $allowedExtensions = config('jsonapi.extensions', []);

        $contentType = $request->header('Content-Type');
        $methodsWithBody = ['POST', 'PATCH', 'PUT'];

        if (in_array($request->method(), $methodsWithBody)) {
            if (! $contentType) {
                return $this->error([[
                    'status' => Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
                    'title' => __('api.unsupported_media_type.title'),
                    'detail' => __('api.unsupported_media_type.detail')
                ]]);
            }
        }

        if ($contentType) {
            if (! str_starts_with($contentType, $allowedMediaType)) {
                return $this->error([[
                    'status' => Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
                    'title' => __('api.unsupported_media_type.title'),
                    'detail' => __('api.unsupported_media_type.detail')
                ]]);
            }

            $parts = array_map('trim', explode(';', $contentType));
            array_shift($parts);

            foreach ($parts as $param) {
                if (! $param) continue;

                [$key, $value] = array_pad(explode('=', $param, 2), 2, null);

                if (! in_array($key, ['ext', 'profile'])) {
                    return $this->error([[
                        'status' => Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
                        'title' => __('api.unsupported_media_type.title'),
                        'detail' => __('api.unsupported_media_type.parameter_not_supported', ['parameter' => $key])
                    ]]);
                }

                if ($key === 'ext' && $value) {
                    $uris = explode(' ', trim($value, '"'));

                    foreach ($uris as $uri) {
                        if (! in_array($uri, $allowedExtensions)) {
                            return $this->error([[
                                'status' => Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
                                'title' => __('api.unsupported_media_type.title'),
                                'detail' => __('api.unsupported_media_type.extension_not_supported', ['extension' => $uri])
                            ]]);
                        }
                    }
                }
            }
        }

        return $this->addContentTypeHeader($next($request));
    }

    private function addContentTypeHeader(Response $response): Response
    {
        $response->headers->set('Content-Type', 'application/vnd.api+json');

        return $response;
    }
}
