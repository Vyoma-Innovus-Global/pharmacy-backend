# Swagger API Documentation

## 📚 Overview

Complete API documentation has been generated using L5-Swagger for the Pharmacy Management System.

## 🌐 Access Swagger UI

### Local Development
```
http://127.0.0.1:8000/api/documentation
```

### Production
```
https://your-domain.com/api/documentation
```

## 🚀 What's Documented

### ✅ Documented APIs

1. **Admin - Student Marks**
   - `POST /api/admin/save-student-marks` - Save student marks (bulk)

2. **Student Marks**
   - `POST /api/marks/student-marks-info-v1` - Get student marks information

3. **Payment**
   - `POST /api/institute-payment` - Initiate institute payment (SBI ePay)

## 🔐 Authentication

All APIs use **token-based authentication**. 

**Header Name:** `token`  
**Example:** `token: 8a0c2afc9b007eede34ff3d384988490`

### How to Test in Swagger UI:

1. Click the **"Authorize"** button (🔒 icon) at the top right
2. Enter your token in the **"token"** field
3. Click **"Authorize"**
4. Click **"Close"**
5. Now all API requests will include your token automatically

## 📝 How to Add More API Documentation

To document additional APIs, add Swagger annotations above the controller methods:

```php
/**
 * @OA\Post(
 *     path="/api/your-endpoint",
 *     tags={"Your Tag"},
 *     summary="Short description",
 *     description="Detailed description",
 *     security={{"token": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="field1", type="string", example="value1"),
 *             @OA\Property(property="field2", type="integer", example=123)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Success response",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=1),
 *             @OA\Property(property="message", type="string", example="Success")
 *         )
 *     )
 * )
 */
public function yourMethod(Request $request)
{
    // Your code here
}
```

Then regenerate documentation:
```bash
php artisan l5-swagger:generate
```

## 🏷️ Available Tags

- **Authentication** - User authentication and authorization
- **Admin - Teacher** - Teacher management operations
- **Admin - Student Marks** - Student marks management
- **Student Marks** - Student marks information retrieval
- **Payment** - Payment gateway integration
- **Admin - Master Data** - Master data endpoints

## 📖 API Response Format

All APIs follow a standard response format:

### Success Response
```json
{
  "version": "1.0",
  "status": 1,
  "message": "Success message",
  "data": { ... }
}
```

### Error Response
```json
{
  "version": "1.0",
  "status": 0,
  "message": "Error message",
  "data": []
}
```

## 🔄 Regenerate Documentation

After adding or updating API annotations:

```bash
cd /Users/indranilsarmacharya/Documents/pharmacy_final/services
php artisan l5-swagger:generate
```

## 📁 Files Location

- **Swagger Config:** `config/l5-swagger.php`
- **Generated JSON:** `storage/api-docs/api-docs.json`
- **Main Documentation:** `app/Http/Controllers/Controller.php` (base info)
- **Route:** `/api/documentation` (defined in l5-swagger config)

## 🛠️ Useful Commands

```bash
# Generate documentation
php artisan l5-swagger:generate

# Clear and regenerate
php artisan l5-swagger:generate --all

# Publish config (if needed again)
php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
```

## 📚 Documentation Resources

- [L5-Swagger GitHub](https://github.com/DarkaOnLine/L5-Swagger)
- [OpenAPI 3.0 Specification](https://swagger.io/specification/)
- [Swagger PHP Annotations](https://zircote.github.io/swagger-php/)

## ✨ Features

- ✅ Interactive API testing directly in browser
- ✅ Automatic request/response examples
- ✅ Authentication testing with token
- ✅ Model schemas
- ✅ Request validation examples
- ✅ Response codes and descriptions
- ✅ Export as JSON/YAML

## 🎯 Next Steps

To document all remaining APIs:

1. Add `@OA\` annotations to controller methods in:
   - `AdminTeacherController.php`
   - `AdminDepartmentController.php`
   - `AdminSubjectController.php`
   - `AdminSemesterController.php`
   - `PaymentController.php` (remaining methods)
   - Any other controllers

2. Run: `php artisan l5-swagger:generate`

3. Refresh Swagger UI to see updated documentation

---

**Ready to use! 🎉** Visit `http://127.0.0.1:8000/api/documentation` to explore your APIs!
