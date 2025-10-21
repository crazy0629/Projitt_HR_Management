import { z } from 'zod';

/**
 * Returns a Zod schema for signup form validation.
 * Validates fields: email, companyName, firstName, lastName, country,
 * phoneNumber, industry, companySize, interests, and acceptance of terms.
 *
 * @returns {z.ZodObject} The Zod validation schema for signup
 */
export const getSignupSchema = (): z.ZodObject<any> => {
  return z.object({
    email: z
      .string()
      .min(1, { message: 'Email is required.' })
      .email({ message: 'Please enter a valid email address.' }),
    companyName: z
      .string()
      .min(1, { message: 'Name is required.' })
      .min(2, { message: 'Name must be at least 2 characters long.' }),
    firstName: z
      .string()
      .min(1, { message: 'First Name is required.' })
      .min(2, { message: 'First Name must be at least 2 characters long.' }),
    lastName: z
      .string()
      .min(1, { message: 'Last Name is required.' })
      .min(2, { message: 'Last Name must be at least 2 characters long.' }),
    country: z.string().min(1, { message: 'Country is required.' }),
    phoneNumber: z
      .string()
      .min(10, { message: 'Phone number must be at least 10 digits.' })
      .max(15, { message: 'Phone number must be at most 15 digits.' })
      .regex(/^\+?[0-9]{10,15}$/, { message: 'Phone number must be valid.' }),
    industry: z.string().min(1, { message: 'Industry is required.' }),
    companySize: z.string().min(1, { message: 'Company Size is required.' }),
    interests: z.array(z.string()).min(1, { message: 'At least one interest is required.' }),
    accept: z.boolean().refine((val) => val === true, {
      message: 'You must accept the terms and conditions.',
    }),
  });
};

/** Type representing the validated signup form data */
export type SignupSchemaType = z.infer<ReturnType<typeof getSignupSchema>>;
