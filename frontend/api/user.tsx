/**
 * AuthService.ts
 *
 * This module provides utility functions for user authentication including:
 * - Login
 * - Logout
 * - Password reset
 * - Forgot password
 *
 * Note: These functions use axios to interact with the backend API.
 */

import axios from "axios";
import { customToast } from "@/components/common/toastr";

/**
 * Sends a PUT request to log in a user with email and password.
 *
 * @param {Object} params - The login credentials.
 * @param {string} params.email - User's email address.
 * @param {string} params.password - User's password.
 * @returns {Promise<any>} - Axios response promise.
 */
export const login = async ({
    email,
    password,
}: {
    email: string;
    password: string;
}): Promise<any> => {
    return axios.put(`${process.env.NEXT_PUBLIC_BASE_PATH}/user/login`, {
        email,
        password,
    });
};

/**
 * Sends a POST request to reset the user's password.
 *
 * @param {Object} params - The reset data.
 * @param {string} params.token - Reset token sent via email.
 * @param {string} params.email - User's email address.
 * @param {string} params.password - New password.
 * @returns {Promise<any>} - Axios response promise.
 */
export const resetPassword = async ({
    token,
    email,
    password,
}: {
    token: string;
    email: string;
    password: string;
}): Promise<any> => {
    return axios.post(`${process.env.NEXT_PUBLIC_BASE_PATH}/user/password-reset`, {
        token,
        email,
        password,
        password_confirmation: password,
    });
};

/**
 * Sends a POST request to initiate the forgot password process.
 *
 * @param {Object} params - The email data.
 * @param {string} params.email - User's email address.
 * @returns {Promise<any>} - Axios response promise.
 */
export const forgotPasssword = async ({
    email,
}: {
    email: string;
}): Promise<any> => {
    return axios.post(`${process.env.NEXT_PUBLIC_BASE_PATH}/user/forgot-password`, {
        email,
        type_id: 1,
    });
};

export const refreshToken = async (token: string): Promise<any> => {
    return axios.post(`${process.env.NEXT_PUBLIC_BASE_PATH}/user/refresh-token`, {}, {
        headers: {
            "refresh-token": token,
        }
    });
};

/**
 * Sends a GET request to log out the currently authenticated user.
 *
 * @returns {Promise<any>} - Axios response promise.
 */
export const logout = async (): Promise<any> => {
    return axios.get(`${process.env.NEXT_PUBLIC_BASE_PATH}/user/logout`);
};
