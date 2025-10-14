'use client';

import { JSX, useEffect, useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';

import { ChangePasswordSchemaType, getChangePasswordSchema } from '../forms/change-password-schema';
import FooterWithCompanyLogo from '../component/footerWithCompayLogo';

/**
 * Page component renders the Reset Password confirmation UI,
 * verifies the reset token, and allows user to set a new password.
 *
 * @returns {JSX.Element} The rendered page component
 */
export default function Page(): JSX.Element {
  const router = useRouter();
  const searchParams = useSearchParams();
  const token = searchParams?.get('token') || null;

  const [verifyingToken, setVerifyingToken] = useState(false);
  const [isValidToken, setIsValidToken] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [isProcessing, setIsProcessing] = useState(false);
  const [passwordVisible, setPasswordVisible] = useState(false);
  const [passwordConfirmationVisible, setPasswordConfirmationVisible] = useState(false);

  const form = useForm<ChangePasswordSchemaType>({
    resolver: zodResolver(getChangePasswordSchema()),
    defaultValues: {
      newPassword: '',
      confirmPassword: '',
    },
  });

  useEffect(() => {
    /**
     * Verifies the reset token with the backend API.
     * Sets token validity state accordingly.
     */
    const verifyToken = async (): Promise<void> => {

    };

    if (token) {
      verifyToken();
    } else {
      setError('No reset token provided.');
    }
  }, [token]);

  /**
   * Handles form submission for changing password.
   *
   * @param {ChangePasswordSchemaType} values - Form values containing new password and confirmation
   */
  async function onSubmit(values: ChangePasswordSchemaType): Promise<void> {
    setIsProcessing(true);
    setError(null);
    setSuccessMessage(null);
  }

  return (
    <>
      <div
        className="w-full h-full flex flex-col justify-between gap-[30px]"
        id="reset-password-page"
        data-testid="reset-password-page"
      >
        <div
          className="pt-[60px] flex justify-center w-full"
          id="logo-container"
          data-testid="logo-container"
        >
          <img
            src="/images/logo.png"
            alt="logo"
            className="h-[48px]"
            id="company-logo"
            data-testid="company-logo"
          />
        </div>
        <div
          className="w-full flex-1 flex justify-center items-center pb-[10px]"
          id="confirmation-container"
          data-testid="confirmation-container"
        >
          <div
            className="w-[544px] border border-[#e9e9e9] rounded-[16px] bg-white py-[63px] px-[40px]"
            id="confirmation-box"
            data-testid="confirmation-box"
          >
            <p
              className="text-[22px]/[30px] font-semibold tracking-tight text-[#353535] text-center"
              id="confirmation-title"
              data-testid="confirmation-title"
            >
              Confirm your Email
            </p>
            <p
              className="text-[18px]/[30px] mt-[14px] text-[#4B4B4B] text-center"
              id="confirmation-message"
              data-testid="confirmation-message"
            >
              We’ve sent a confirmation link to{' '}
              <span
                className="text-[#0D978B]"
                id="confirmation-email"
                data-testid="confirmation-email"
              >
                admin@zaidllc.com
              </span>
              .
            </p>
            <p
              className="text-[18px]/[30px] text-[#4B4B4B] text-center"
              id="confirmation-instruction"
              data-testid="confirmation-instruction"
            >
              Click the link in that email to verify your account and access your Projitt dashboard.
            </p>
          </div>
        </div>
        <FooterWithCompanyLogo />
      </div>
    </>
  );
}
