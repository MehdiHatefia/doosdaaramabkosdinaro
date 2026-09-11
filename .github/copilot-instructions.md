# Project Architecture & Knowledge Graph Rules

1. This is a Laravel project. Always follow clean architecture.
2. Whenever you generate or modify Models, Migrations, Controllers, or Routes, you MUST update the Mermaid graph in `docs/architecture.md`.
3. The graph must clearly show:
   - Database entities and Eloquent relations (e.g. User ||--o{ Post : hasMany).
   - Core domain actions and services.

   ## Comprehensive Logging Standards (Mandatory)
- Backend (Laravel 11): 
  * Add structured context logging (`Log::info`, `Log::warning`, `Log::error`) inside every Controller action, Service method, and Exception catch block.
  * Always include contextual data: `['function' => __METHOD__, 'user_id' => ..., 'payload' => ..., 'trace' => ...]` (never log sensitive data like raw credentials or plaintext tokens).
- Frontend (Vue 3 / Vite):
  * Create/use a centralized Logger utility (`src/utils/logger.js`) that wraps `console.log/warn/error` with prefixes `[ModuleName:FunctionName]`.
  * Add logs to all Pinia store actions, composables, API request/response interceptors (Axios/Fetch), and route navigation guards.
  * In production builds, mute verbose logs while preserving error reporting.


  ## Authentication & OTP Architecture Rule (Mandatory)
- Never use Cache (Redis, file, memory) for OTP storage or validation.
- All OTP processes MUST be persisted and validated strictly through the database using an `otps` table.
- Expired or consumed OTPs must be tracked via database columns (`expires_at`, `consumed_at`).