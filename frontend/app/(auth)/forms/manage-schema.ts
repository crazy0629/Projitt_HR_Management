import { z } from 'zod';

/**
 * Returns a Zod schema for managing selected management areas.
 * Validates that at least one management area is selected.
 *
 * @returns {z.ZodObject} The Zod validation schema for management areas
 */
export const getManageSchema = (): z.ZodObject<any> => {
  return z.object({
    managementAreas: z
      .array(z.string())
      .min(1, { message: 'At least one management area is required.' }),
  });
};

/** Type representing the validated management areas data */
export type ManageSchemaType = z.infer<ReturnType<typeof getManageSchema>>;
