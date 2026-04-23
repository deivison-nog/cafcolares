-- =============================================================================
-- MIGRATION SCRIPT: MD5 → bcrypt (password_hash) for existing users
-- =============================================================================
-- CONTEXT:
--   The application previously stored passwords hashed with MD5.
--   The new code in estabelecimentos.php uses PHP's password_hash() with
--   PASSWORD_DEFAULT (bcrypt) for all newly created users.
--
-- IMPORTANT: MD5 hashes CANNOT be automatically converted to bcrypt because
--   MD5 is a one-way hash and the original plaintext passwords are unknown.
--
-- STEPS TO MIGRATE:
--   1. After deploying the updated code, have each existing user log in.
--      The login page (index.php) handles both old MD5 and new bcrypt hashes.
--   2. Once a user logs in successfully with their MD5 password, the code
--      automatically rehashes it with bcrypt and updates the record.
--   3. After all users have logged in at least once, the MD5 fallback code
--      in index.php can be removed.
--
-- ALTERNATIVELY (admin-forced reset):
--   Run the queries below to flag accounts that still use MD5 and set a
--   temporary known password that admins share out-of-band, then require
--   users to change it on first login.
-- =============================================================================

-- Step 1: Add a column to track when the password was last changed.
--         This helps administrators monitor migration progress.
--         UNCOMMENT AND RUN AFTER TAKING A BACKUP:
--
-- ALTER TABLE usuarios
--   ADD COLUMN senha_atualizada_em TIMESTAMP NULL DEFAULT NULL
--   COMMENT 'Timestamp of last password change; NULL means password not yet migrated to bcrypt';

-- Step 2: Helper view – identifies accounts still using MD5 passwords.
--         bcrypt hashes always start with '$2y$'; MD5 hashes are 32 hex chars.

CREATE OR REPLACE VIEW v_usuarios_md5_pendentes AS
SELECT id, usuario, nivel_acesso
FROM usuarios
WHERE senha NOT LIKE '$2y$%'   -- not a bcrypt hash
  AND LENGTH(senha) = 32;      -- MD5 produces exactly 32 hex characters

-- Step 3 (OPTIONAL – admin reset approach):
--   Set a temporary bcrypt password ('Mudar@123') for all accounts still
--   using MD5. Communicate this temporary password to affected users and
--   require them to change it on first login.
--
--   UNCOMMENT ONLY AFTER TESTING IN A STAGING ENVIRONMENT:
--
-- UPDATE usuarios
-- SET senha = '$2y$12$REPLACE_WITH_REAL_BCRYPT_HASH_HERE',
--     senha_atualizada_em = NOW()
-- WHERE senha NOT LIKE '$2y$%'
--   AND LENGTH(senha) = 32;
--
--   To generate the correct bcrypt hash for 'Mudar@123', run in PHP:
--     echo password_hash('Mudar@123', PASSWORD_DEFAULT);
--   Then replace the placeholder hash above with the real output.

-- =============================================================================
-- NOTE: The login page (index.php) has already been updated to handle both
--   old MD5 and new bcrypt during the transition period:
--
--   $user = /* fetch user by username */;
--   if ($user) {
--       $valid = false;
--       if (password_verify($inputPassword, $user['senha'])) {
--           // New bcrypt hash – valid
--           $valid = true;
--       } elseif (md5($inputPassword) === $user['senha']) {
--           // Legacy MD5 hash – valid, rehash now
--           $newHash = password_hash($inputPassword, PASSWORD_DEFAULT);
--           $stmt = $pdo->prepare('UPDATE usuarios SET senha = ?, senha_atualizada_em = NOW() WHERE id = ?');
--           $stmt->execute([$newHash, $user['id']]);
--           $valid = true;
--       }
--       if ($valid) { /* start session */ }
--   }
-- =============================================================================
