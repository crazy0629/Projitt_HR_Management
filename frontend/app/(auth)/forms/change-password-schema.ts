import { z } from 'zod';
import { getPasswordSchema } from './password-schema';

/**
 * Returns a Zod schema for the change password form validation.
 * Validates email, newPassword, and confirmPassword fields,
 * and checks that newPassword and confirmPassword match.
 *
 * @returns {z.ZodObject} The Zod validation schema for change password form
 */
export const getChangePasswordSchema = () => {
  return z
    .object({
      email: z.string().email({ message: "Invalid email" }),
      newPassword: z.string(),
      confirmPassword: z.string(),
    })
    .refine((data) => data.newPassword === data.confirmPassword, {
      message: "Passwords do not match",
      path: ["confirmPassword"],
    });
};

/** Type representing the validated data shape for change password form */
export type ChangePasswordSchemaType = z.infer<ReturnType<typeof getChangePasswordSchema>>;

/**
 * Returns a Zod schema for the API payload to change password.
 * Validates token and newPassword fields.
 *
 * @returns {z.ZodObject} The Zod validation schema for change password API
 */
export const getChangePasswordApiSchema = () => {
  return z.object({
    token: z.string().nonempty({
      message: "A valid token is required to change the password.",
    }),
    newPassword: getPasswordSchema(),
  });
};

/** Type representing the validated data shape for change password API payload */
export type ChangePasswordApiSchemaType = z.infer<ReturnType<typeof getChangePasswordApiSchema>>;
