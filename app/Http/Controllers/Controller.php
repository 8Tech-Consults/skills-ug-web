<?php

namespace App\Http\Controllers;

use OpenApi\Annotations as OA;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="8Jobspot API Documentation",
 *      description="API documentation for 8Jobspot."
 * )
 * @OA\Server(
 *      url="http://localhost/skills-ug-web/api",
 *      description="Local server"
 * )
 * @OA\Server(
 *      url="https://skills-ug-api.8technologies.net/api",
 *      description="Production server"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
