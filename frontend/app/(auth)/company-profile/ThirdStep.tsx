'use client';

import { useState } from 'react';
import { ManageSchemaType } from '../forms/manage-schema';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import Select from 'react-select';
import { Button } from '@/components/ui/button';
import { JSX } from 'react';
type OptionType = { value: string; label: string };

/**
 * ThirdStep component handles the UI and logic for step 3 of the company profile completion process.
 * It allows users to specify if they operate in multiple countries or regions,
 * and select multiple countries if applicable.
 *
 * @param {object} props - Component props
 * @param {string} props.step - Current step identifier
 * @param {React.Dispatch<React.SetStateAction<string>>} props.setStep - Function to update current step
 * @returns {JSX.Element} The rendered component
 */
interface ThirdStepProps {
  step: string;
  setStep: React.Dispatch<React.SetStateAction<string>>;
}

export default function ThirdStep({ step, setStep }: ThirdStepProps): JSX.Element {
  // State for child selections (currently unused but retained as per requirement)
  const [childSelections, setChildSelections] = useState<{ [key: string]: string[] }>({});
  // State to track if user operates in multiple countries
  const [multipleCountries, setMultipleCountries] = useState(false);
  // State to hold selected countries in multi-select
  const [selected, setSelected] = useState<OptionType[]>([]);

  /**
   * Handles form submission by logging the form values and advancing to next step.
   *
   * @param {ManageSchemaType} values - The form values submitted
   * @returns {Promise<void>} A promise that resolves when submission completes
   */
  const onSubmit = async (values: ManageSchemaType): Promise<void> => {
    console.log(values);
    setStep('step4');
  };

  // Options for country selection dropdown
  const options: OptionType[] = [
    { value: 'uk', label: 'United Kingdom' },
    { value: 'dubai', label: 'Dubai' },
    { value: 'sweden', label: 'Sweden' },
    { value: 'germany', label: 'Germany' },
    { value: 'france', label: 'France' },
  ];

  // Custom styles for react-select component
  const customStyles = {
    control: (base: any) => ({
      ...base,
      borderRadius: '10px',
      borderColor: '#ccc',
      boxShadow: 'none',
      padding: '2px 4px',
      minHeight: '50px',
    }),
    placeholder: (base: any) => ({
      ...base,
      color: '#444',
      fontSize: '16px',
    }),
    multiValue: (base: any) => ({
      ...base,
      backgroundColor: '#d1eeee',
      borderRadius: '12px',
      padding: '2px 8px',
    }),
    multiValueLabel: (base: any) => ({
      ...base,
      color: '#00796b',
      fontWeight: 500,
    }),
    multiValueRemove: (base: any) => ({
      ...base,
      color: '#00796b',
      cursor: 'pointer',
      ':hover': {
        backgroundColor: '#b2dfdb',
        color: '#004d40',
      },
    }),
    dropdownIndicator: (base: any) => ({
      ...base,
      padding: '4px',
    }),
  };

  return (
    <div
      className="sm:w-[1000px] w-[100%] border border-[#e9e9e9] rounded-[16px] bg-white py-[60px] px-[48px]"
      id="third-step-container"
      data-testid="third-step-container"
    >
      <div className="flex sm:flex-row flex-col items-center justify-between">
        <p
          className="text-[22px]/[30px] font-semibold tracking-tight text-[#353535] sm:text-left text-center"
          id="third-step-title"
          data-testid="third-step-title"
        >
          Complete your company profile
        </p>
        <p
          className="text-[16px]/[26px] text-[#353535] sm:mt-0 mt-[10px]"
          id="third-step-progress"
          data-testid="third-step-progress"
        >
          Step 3/4
        </p>
      </div>
      <div className="w-full h-[3px] bg-[#e9e9e9] md:mt-[32px] mt-[10px]">
        <div className="w-[60%] h-[3px] bg-[#0d978b]" />
      </div>
      <div className="sm:w-[486px] w-[100%] mx-auto mt-[44px]">
        <p className="text-[18px]/[30px] text-[#353535]" id="multiple-countries-question" data-testid="multiple-countries-question">
          Do you operate in multiple countries or regions?
        </p>
        <RadioGroup
          className="flex flex-col gap-[16px] mt-[12px]"
          onValueChange={(e) => {
            console.log(e);
            setMultipleCountries(e === 'yes');
          }}
          value={multipleCountries ? 'yes' : 'no'}
          id="multiple-countries-radio-group"
          data-testid="multiple-countries-radio-group"
        >
          <div className="flex items-center space-x-2">
            <RadioGroupItem value="yes" id="multiple-countries-yes" />
            <Label
              htmlFor="multiple-countries-yes"
              variant="secondary"
              className="text-[14px]/[20px]"
              id="label-multiple-countries-yes"
              data-testid="label-multiple-countries-yes"
            >
              Yes
            </Label>
          </div>
          <div className="flex items-center space-x-2">
            <RadioGroupItem value="no" id="multiple-countries-no" />
            <Label
              htmlFor="multiple-countries-no"
              variant="secondary"
              className="text-[14px]/[20px]"
              id="label-multiple-countries-no"
              data-testid="label-multiple-countries-no"
            >
              No
            </Label>
          </div>
        </RadioGroup>
        {/* Multi-select using Radix UI Select primitives */}
        {multipleCountries && (
          <div className="mt-8">
            <Label
              className="text-[16px]/[26px] mb-[12px] block"
              id="select-countries-label"
              data-testid="select-countries-label"
            >
              Select Countries
            </Label>
            <Select<OptionType, true>
              options={options}
              value={selected}
              onChange={(e) => setSelected(e as OptionType[])}
              isMulti
              placeholder="Choose countries"
              styles={customStyles}
              inputId="select-countries"
              instanceId="select-countries"
              aria-labelledby="select-countries-label"
              data-testid="select-countries"
            />
          </div>
        )}

        <div
          className="px-[10px] flex gap-[10px] mt-[100px] justify-center"
          id="third-step-buttons"
          data-testid="third-step-buttons"
        >
          <Button
            type="button"
            onClick={() => setStep('step2')}
            className="w-[151px] bg-white text-[#053834] border-[#E9E9E9] border-[1px] h-[48px] hover:border-[#053834]  hover:bg-white"
            id="back-button"
            data-testid="back-button"
          >
            Back
          </Button>
          <Button
            onClick={() => setStep('step4')}
            className="w-[151px] h-[48px]"
            id="continue-button"
            data-testid="continue-button"
          >
            Continue
          </Button>
        </div>
      </div>
    </div>
  );
}
