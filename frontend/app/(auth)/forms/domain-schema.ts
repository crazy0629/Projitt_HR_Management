import { z } from 'zod';

/**
 * Returns a Zod schema for validating domain input.
 * Validates that domain is a non-empty string with at least 2 characters.
 *
 * @returns {z.ZodObject} The Zod validation schema for domain
 */
export const getDomainSchema = (): z.ZodObject<any> => {
  return z.object({
    domain: z
      .string()
      .min(1, { message: 'Domain is required.' })
      .min(2, { message: 'Domain must be at least 2 characters long.' }),
  });
};

/** Type representing the validated domain form data */
export type DomainSchemaType = z.infer<ReturnType<typeof getDomainSchema>>;
