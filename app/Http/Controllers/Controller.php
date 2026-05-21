<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="Pharmacy Management System API",
 *     version="1.0.0",
 *     description="Complete API documentation for Pharmacy Management System including admin, student, teacher, and payment modules",
 *     @OA\Contact(
 *         email="support@pharmacy.edu"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://127.0.0.1:8000",
 *     description="Local Development Server"
 * )
 *
 * @OA\Server(
 *     url="https://api.pharmacy.edu",
 *     description="Production Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="token",
 *     type="apiKey",
 *     in="header",
 *     name="token",
 *     description="Enter your authentication token"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your bearer token"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication and authorization endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Admin - Teacher",
 *     description="Teacher management operations"
 * )
 *
 * @OA\Tag(
 *     name="Admin - Student Marks",
 *     description="Student marks management operations"
 * )
 *
 * @OA\Tag(
 *     name="Student Marks",
 *     description="Student marks information retrieval"
 * )
 *
 * @OA\Tag(
 *     name="Payment",
 *     description="Payment gateway integration endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Admin - Master Data",
 *     description="Master data endpoints (departments, subjects, semesters, etc.)"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
