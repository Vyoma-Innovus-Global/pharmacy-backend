# Root Cause Analysis (RCA): Intermittent 401 Unauthorized Issue

## 1. Executive Summary

| Attribute | Details |
| :--- | :--- |
| **Issue** | Intermittent `401 Unauthorized` responses on authenticated API endpoints (e.g., `/api/admin/institutes`), even immediately after receiving a valid token from `/api/generate-otp/verify`. |
| **Root Cause** | In `App\Models\Token`, `$primaryKey` was incorrectly declared as `'t_token'` instead of `'t_id'`. Eloquent defaulted to treating `t_token` as an auto-incrementing integer, causing hex MD5 string tokens to be cast to `0` or truncated when saved to the database. |
| **Resolution** | Changed `$primaryKey = 't_id'` in [Token.php](file:///d:/git/pharmacy_services/services/app/Models/Token.php). |
| **Status** | Fixed & Verified |

---

## 2. Investigation & Debugging Process (First Principles)

### Phase 1: Analyzing the Client-Side Symptoms
- In the browser network tab, the frontend called `/api/generate-otp/verify` and received a successful response with:
  ```json
  {
    "error": false,
    "message": "OTP Used Successfully.",
    "token": "be00d2a605e3bdf27dac5ff8fa7fc36e",
    "token_expired_on": "2026-08-31 15:40:03",
    "user": { "adminUserId": 5447, ... }
  }
  ```
- Immediately following this, requests were sent to `/api/admin/institutes` containing both:
  - `token: be00d2a605e3bdf27dac5ff8fa7fc36e`
  - `Authorization: Bearer be00d2a605e3bdf27dac5ff8fa7fc36e`
- However, the server returned **`401 Unauthorized`**.

### Phase 2: Inspecting the Authentication Middleware
We inspected [checkAuthToken.php](file:///d:/git/pharmacy_services/services/app/Http/Middleware/checkAuthToken.php#L12-L46):
```php
if ($request->header('token')) {
    $token = $request->header('token');
    $token_res = Token::select('t_token', 't_expired_on', 't_user_id')
        ->where('t_token', $token)
        ->first();

    if ($token_res) {
        if ($token_res->t_expired_on < now()) {
            return response()->json(['error' => true, 'message' => 'Token Expired, Login Again'], 401);
        } else {
            return $next($request);
        }
    } else {
        return response()->json(['error' => true, 'message' => 'Invalid Token, Login Again'], 401);
    }
}
```
**Key Observation:** The middleware performs an exact match lookup in `pharmacy_tokens` using `where('t_token', $token)`. Since the token header was provided and not expired, the query must have returned `null`.

### Phase 3: Inspecting Application Logs
We checked `storage/logs/laravel-2026-08-31.log`:
```text
[2026-08-31 11:40:03] local.INFO: [verifyOtp] Token saved {"user_id":5447,"expiry":"2026-08-31 15:40:03"} 
[2026-08-31 11:40:03] local.INFO: [verifyOtp] OUTPUT (200) {"token":"be00d2a605e3bdf27dac5ff8fa7fc36e","user_id":5447}
```
The token generator generated the token string `be00d2a605e3bdf27dac5ff8fa7fc36e` and claimed it was saved.

### Phase 4: Inspecting the Live PostgreSQL Database
We queried the actual row in table `pharmacy_tokens` for user ID `5447`:
```sql
SELECT t_id, t_user_id, t_token, t_generated_on, t_expired_on 
FROM pharmacy_tokens 
WHERE t_user_id = 5447;
```
**Output:**
```json
{
  "t_id": 11219,
  "t_user_id": 5447,
  "t_token": 0,
  "t_generated_on": "2026-08-31 11:40:03",
  "t_expired_on": "2026-08-31 15:40:03"
}
```
**Crucial Finding:** The database stored `t_token = 0` instead of `"be00d2a605e3bdf27dac5ff8fa7fc36e"`.

### Phase 5: Checking Database Schema vs. Model Definition
1. **Database Schema of `pharmacy_tokens`:**
   - `t_id`: `bigint` (Primary Key, auto-incrementing)
   - `t_user_id`: `integer`
   - `t_token`: `text`
   - `t_generated_on`: `text`
   - `t_expired_on`: `text`

2. **Model Definition in [app/Models/Token.php](file:///d:/git/pharmacy_services/services/app/Models/Token.php):**
   ```php
   class Token extends Model
   {
       protected $table        = 'pharmacy_tokens';
       protected $primaryKey   = 't_token'; // ❌ Inconsistency
       public $timestamps      = false;
   }
   ```

---

## 3. The Core Mechanism of the Bug

1. In Laravel Eloquent, every model assumes:
   - `public $incrementing = true;`
   - `protected $keyType = 'int';`
2. Because `t_token` was configured as `$primaryKey`:
   - Eloquent treated `t_token` as an integer primary key.
3. During `Token::updateOrCreate(['t_user_id' => $adminUserId], ['t_token' => $token, ...])`:
   - Eloquent attempted to set and cast the key attribute `t_token` as an integer `(int) $token`.
   - In PHP, casting a hexadecimal string starting with characters (like `"be00..."` or `"a4f..."`) to an integer results in `0`.
   - If the hash happened to start with numbers (like `"79819abc..."`), it was cast to `79819`.
4. Eloquent wrote `0` into the PostgreSQL table while the controller returned the un-cast string `"be00d2a605e3bdf27dac5ff8fa7fc36e"` to the frontend.
5. On the next API request, the middleware queried `WHERE t_token = 'be00d2a605e3bdf27dac5ff8fa7fc36e'`, which failed to find a match and triggered `401 Unauthorized`.

---

## 4. The Fix Applied

Modified [app/Models/Token.php](file:///d:/git/pharmacy_services/services/app/Models/Token.php#L8-L16):

```diff
 namespace App\Models;

 use Illuminate\Database\Eloquent\Factories\HasFactory;
 use Illuminate\Database\Eloquent\Model;

 class Token extends Model
 {
     use HasFactory;
     protected $table        =   'pharmacy_tokens';
-    protected $primaryKey   =   't_token';
+    protected $primaryKey   =   't_id';
     public $timestamps      =   false;

     protected $guarded = [];
 }
```

---

## 5. Verification

1. Executed `Token::updateOrCreate` with a new randomized MD5 token (`d7de5e8dd6454ae321c35449a04323f0`).
2. Checked database storage:
   - `t_token` correctly stored the full text string `"d7de5e8dd6454ae321c35449a04323f0"`.
3. Executed `Token::where('t_token', $token)->first()`.
   - The query successfully located the record and returned the valid user data.
