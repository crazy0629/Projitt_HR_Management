import { z } from 'zod';

/**
 * Returns a Zod schema to validate password strength.
 * Password must be a string with a minimum length (default 8)
 * and contain at least one numeric digit.
 *
 * @param {number} minLength - Minimum length required for the password (default 8)
 * @returns {z.ZodString} The Zod validation schema for password
 */
export const getPasswordSchema = (minLength = 8): z.ZodString => {
  return z
    .string()
    .min(minLength, {
      message: `Password must be at least ${minLength} characters long.`,
    })
    .regex(/[a-z]/, {
      message: 'Password must contain at least one lowercase letter.',
    })
    .regex(/[A-Z]/, {
      message: 'Password must contain at least one uppercase letter.',
    })
    .regex(/\d/, {
      message: 'Password must contain at least one number.',
    })
    .regex(/[!@#$%^&*()_+\-=[\]{};':"\\|,.<>/?]/, {
      message: 'Password must contain at least one special character.',
    });
};
