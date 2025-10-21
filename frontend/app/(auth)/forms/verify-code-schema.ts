import { z } from 'zod';

/**
 * Returns a Zod schema for verifying a code.
 * Validates that the code string is at least 6 characters long.
 *
 * @returns {z.ZodObject} The Zod validation schema for verification code
 */
export const getVerifyCodeSchema = (): z.ZodObject<any> => {
  return z.object({
    code: z.string().min(6, { message: 'Enter the code correctly.' }),
  });
};

/** Type representing the verified code form data */
export type VerifyCodeSchemaType = z.infer<ReturnType<typeof getVerifyCodeSchema>>;
