import { customToast } from "@/components/common/toastr";

/**
 * Error response structure from API
 */
interface ErrorResponse {
    response?: {
        data?: {
            errors?: Record<string, unknown[]>;
            message?: string;
        };
    };
}

/**
 * Global error handler utility that parses different types of errors
 * and displays them using customToast with dynamic parameters.
 * 
 * @param error - The error object to handle
 * @param title - Dynamic title for the toast (default: "Error")
 * @param fallbackMessage - Fallback message if error parsing fails (default: "Something went wrong")
 * @returns The parsed error message
 */
export const handleError = (
    error: unknown,
    title: string = "Error",
    fallbackMessage: string = "Something went wrong"
): string => {
    let errorMessage = fallbackMessage;

    // Check if error has response data with error messages
    if (error && typeof error === 'object' && 'response' in error) {
        const errorResponse = (error as ErrorResponse).response?.data;

        if (errorResponse && typeof errorResponse === 'object' && 'errors' in errorResponse) {
            const errors = errorResponse.errors;
            const errorMessages = Object.values(errors)
                .flat()
                .filter((msg: unknown) => typeof msg === 'string')
                .join(", ");

            if (errorMessages) {
                errorMessage = errorMessages;
            }
        } else if (errorResponse && typeof errorResponse === 'object' && 'message' in errorResponse) {
            errorMessage = errorResponse.message as string;
        }
    } else if (error instanceof Error) {
        errorMessage = error.message;
    }

    // Display the error using customToast
    customToast(title, errorMessage, "error");

    return errorMessage;
};

/**
 * Async error handler wrapper for try-catch blocks
 * 
 * @param asyncFunction - The async function to execute
 * @param title - Dynamic title for the toast (default: "Error")
 * @param fallbackMessage - Fallback message if error parsing fails (default: "Something went wrong")
 * @returns Promise with the result or throws the error after handling
 */
export const handleAsyncError = async <T>(
    asyncFunction: () => Promise<T>,
    title: string = "Error",
    fallbackMessage: string = "Something went wrong"
): Promise<T> => {
    try {
        return await asyncFunction();
    } catch (error) {
        handleError(error, title, fallbackMessage);
        throw error; // Re-throw the error for further handling if needed
    }
};

/**
 * Error handler for specific operation types
 */
export const errorHandlers = {
    /**
     * Handle job title errors
     */
    jobTitle: (error: unknown) => handleError(error, "Job Title Error", "Failed to add job title"),

    /**
     * Handle authentication errors
     */
    auth: (error: unknown) => handleError(error, "Authentication Error", "Authentication failed"),

    /**
     * Handle API errors
     */
    api: (error: unknown) => handleError(error, "API Error", "API request failed"),

    /**
     * Handle validation errors
     */
    validation: (error: unknown) => handleError(error, "Validation Error", "Validation failed"),

    /**
     * Handle job posting errors
     */
    jobPosting: (error: unknown) => handleError(error, "Job Posting Error", "Failed to create job posting"),

    /**
     * Handle job description errors
     */
    jobDescription: (error: unknown) => handleError(error, "Job Description Error", "Failed to process job description"),

    /**
     * Handle general errors with custom title
     */
    custom: (error: unknown, title: string, fallbackMessage?: string) =>
        handleError(error, title, fallbackMessage || "Operation failed"),
};