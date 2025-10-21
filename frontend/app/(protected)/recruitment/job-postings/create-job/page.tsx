"use client";

import { Button } from "@/components/ui/button";
import Stepper from "./components/stepper";
import JobDetails from "./components/job-details";
import JobDescription from "./components/job-description";
import ApplicantQuestions, { ApplicantQuestionsRef } from "./components/applicant-questions";
import HiringPipeline from "./components/hiring-pipeline";
import { JSX, useState, useEffect, useRef } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import Publish from "./components/publish";
import Completed from "./components/completed";
import { addNewJobTitle, getDesignation } from "@/api/basic"
import { addNewDetailJob, editJobDescription, editDetailJob, getJobDetails, publishJob } from "@/api/job-posting";
import { useBasic } from "@/context/BasicContext";
import { errorHandlers } from "@/utils/error-handler";

/**
 * JobData type defines the structure of data related to a job posting.
 */
type JobData = {
    title: string;
    department_id: string;
    employment_type_id: string;
    no_of_job_opening: string;
    skill_ids: string[];
    location_type_id: number;
    state: string;
    country_id?: string;
    salary: string;
    deadline: Date;
    description: string;
};

/**
 * Skill type defines the structure of a skill object.
 */
type Skill = {
    id: number;
    name: string;
};

/**
 * Designation type defines the structure of a designation object.
 */
type Designation = {
    id: number;
    name: string;
};

/**
 * JobSkill type defines the structure of a skill object from job details API.
 */
type JobSkill = {
    id: number;
    name: string;
    slug: string;
    description: string;
    type_id: number;
    created_by: number | null;
    created_at: string;
};

/**
 * JobDetailsErrors type defines the structure of validation errors
 * for the job details step.
 */
type JobDetailsErrors = {
    title?: string;
    department_id?: string;
    employment_type_id?: string;
    no_of_job_opening?: string;
    skill_ids?: string;
    location_type_id?: number;
    state?: string;
    country_id?: string;
};

/**
 * JobDescriptionError type defines the structure of validation errors
 * for the job description step.
 */
type JobDesciptionError = {
    description?: string;
};

/**
 * CreateJob component handles the creation of a new job post,
 * including validation and step-based navigation (details, description, questions).
 */
export default function CreateJob(): JSX.Element {
    const router = useRouter();
    const [currentStep, setCurrentStep] = useState<number>(1);
    const [jobData, setJobData] = useState<JobData>({
        title: '',
        department_id: '',
        employment_type_id: '',
        no_of_job_opening: '',
        skill_ids: [],
        location_type_id: 1,
        state: '',
        country_id: '',
        salary: '',
        deadline: new Date(),
        description: ''
    });
    const [publishJobData, setPublishJobData] = useState<any>(null);
    const [errors, setErrors] = useState<JobDetailsErrors>({});
    const [descriptionError, setDescriptionError] = useState<JobDesciptionError>({});
    const [triggerValidation, setTriggerValidation] = useState<boolean>(false);
    const [isLoading, setIsLoading] = useState<boolean>(false);
    const { skills, designation, setDesignation } = useBasic();
    const searchParams = useSearchParams();
    const applicantQuestionsRef = useRef<ApplicantQuestionsRef>(null);

    /**
     * Load job details when editing an existing job
     */
    useEffect(() => {
        const loadJobDetails = async () => {
            const jobId = searchParams.get('id');
            if (jobId && currentStep === 1) {
                try {
                    const response = await getJobDetails(jobId);
                    if (response.status === true) {
                        const jobData = response.data;

                        // Check if job data is null (job doesn't exist)
                        if (!jobData) {
                            // Redirect to create job page without ID parameter
                            router.push('/recruitment/job-postings/create-job');
                            return;
                        }

                        console.log(jobData);
                        // Format salary for display and clean it
                        const cleanSalaryFrom = jobData.salary_from?.toString().replace(/[^$,\-~.\d]/g, '') || '';
                        const cleanSalaryTo = jobData.salary_to?.toString().replace(/[^$,\-~.\d]/g, '') || '';
                        const salary = `$${cleanSalaryFrom} - $${cleanSalaryTo}`;

                        // Extract skill names from skills array
                        const skillNames = jobData.skills.map((skill: JobSkill) => skill.name);

                        setJobData({
                            title: jobData.title || '',
                            department_id: jobData.department_id?.toString() || '',
                            employment_type_id: jobData.employment_type_id?.toString() || '',
                            no_of_job_opening: jobData.no_of_job_opening?.toString() || '',
                            skill_ids: skillNames,
                            location_type_id: jobData.location_type_id || 1,
                            state: jobData.state || '',
                            country_id: jobData.country_id?.toString() || '',
                            salary: salary,
                            deadline: new Date(jobData.deadline),
                            description: jobData.description || ''
                        });
                    }
                } catch (error) {
                    errorHandlers.jobPosting(error);
                }
            }
        };

        loadJobDetails();
    }, [searchParams, currentStep, setJobData]);

    /**
     * Load job data for publish step
     */
    useEffect(() => {
        const loadPublishJobData = async () => {
            const jobId = searchParams.get('id');
            if (jobId && currentStep === 5) {
                try {
                    const response = await getJobDetails(jobId);
                    if (response.status === true) {
                        setPublishJobData(response.data);
                    }
                } catch (error) {
                    errorHandlers.jobPosting(error);
                }
            }
        };

        loadPublishJobData();
    }, [searchParams, currentStep]);

    /**
     * Validates the job details step.
     * @param data - job data from state
     * @returns object containing error messages for invalid fields
     */
    function validateJobDetails(data: JobData): JobDetailsErrors {
        const newErrors: JobDetailsErrors = {};

        if (!data.title?.trim()) newErrors.title = 'Job Title is required.';
        if (!data.department_id) newErrors.department_id = 'Department is required.';
        if (!data.employment_type_id) newErrors.employment_type_id = 'Employment Type is required.';
        if (!data.no_of_job_opening) newErrors.no_of_job_opening = 'No. of Openings is required.';
        if (!data.skill_ids || !Array.isArray(data.skill_ids) || data.skill_ids.length < 1) newErrors.skill_ids = 'At least one skill is required.';
        if (data.location_type_id === 1 || data.location_type_id === 2) {
            if (!data.state) newErrors.state = 'State is required.';
            if (!data.country_id) newErrors.country_id = 'Country is required.';
        } else if (data.location_type_id === 3) {
            if (!data.country_id) newErrors.country_id = 'Country is required.';
        }

        return newErrors;
    }

    /**
     * Validates the job description step.
     * @param data - job data from state
     * @returns object containing error message if description is empty
 */
    function validateJobDescription(data: JobData): JobDesciptionError {
        const newErrors: JobDesciptionError = {};
        const plainText = data.description?.replace(/<[^>]+>/g, '').trim();
        if (!plainText) {
            newErrors.description = 'Job Description is required.';
        }
        return newErrors;
    }

    /**
     * Handles the current step operations (validation, saving, etc.)
     * @param shouldContinue - whether to continue to next step or exit
     * @returns Promise<void>
     */
    const handleStepOperation = async (shouldContinue: boolean): Promise<void> => {
        setIsLoading(true);

        try {
            if (currentStep === 1) {
                const validationErrors = validateJobDetails(jobData);
                setErrors(validationErrors);
                setTriggerValidation(true);
                if (Object.keys(validationErrors).length > 0) {
                    setIsLoading(false);
                    return;
                }

                // Parse salary with validation for different delimiters
                const cleanSalary = jobData.salary.replace(/[^$,\-~.\d]/g, '');
                const salaryParts = cleanSalary.split(/[-~]/);
                let salary_from, salary_to;

                if (salaryParts.length === 1) {
                    // Single value - use same for both from and to
                    salary_from = salaryParts[0].slice(1).replace(/[, ]/g, ''); // Remove first character and clean
                    salary_to = salary_from;
                } else if (salaryParts.length === 2) {
                    // Range value
                    salary_from = salaryParts[0].slice(1).replace(/[, ]/g, ''); // Remove first character and clean
                    salary_to = salaryParts[1].split(' ')[0].slice(1).replace(/[, ]/g, ''); // Remove first character and clean
                } else {
                    // Invalid format - use fallback
                    salary_from = cleanSalary.slice(1).replace(/[, ]/g, '');
                    salary_to = salary_from;
                }

                const payload = {
                    ...jobData,
                    salary_from,
                    salary_to,
                    skill_ids: jobData.skill_ids.map(skillName => {
                        const skill = (skills as Skill[]).find(skill => skill.name === skillName);
                        return skill ? skill.id : null;
                    }).filter(id => id !== null),
                }

                // Check if job title exists in designation
                if (!(designation as Designation[]).some(designation => designation.name === jobData?.title)) {
                    // Add new job title to master data
                    try {
                        await addNewJobTitle(jobData.title);

                        // Refresh designation data after successful addition
                        const designationResponse = await getDesignation();
                        setDesignation(designationResponse.data.data);
                    } catch (error) {
                        errorHandlers.jobTitle(error);
                        setIsLoading(false);
                        return;
                    }
                }

                try {
                    const jobId = searchParams.get('id');
                    let response;

                    if (jobId) {
                        // Editing existing job
                        response = await editDetailJob({ ...payload, id: jobId });
                    } else {
                        // Creating new job
                        response = await addNewDetailJob(payload);
                    }

                    console.log(response);

                    if (response.status === true) {
                        if (shouldContinue) {
                            try {
                                const job_id = jobId || response.data.id;
                                router.push(`/recruitment/job-postings/create-job?id=${job_id}`);
                                setCurrentStep(currentStep + 1);
                            } catch (error) {
                                errorHandlers.jobPosting(error);
                                setIsLoading(false);
                                return;
                            }
                        } else {
                            router.push('/recruitment/job-postings');
                        }
                    }
                } catch (error) {
                    errorHandlers.jobPosting(error);
                    setIsLoading(false);
                    return;
                }
            }

            if (currentStep === 2) {
                const validationErrors = validateJobDescription(jobData);
                setDescriptionError(validationErrors);
                setTriggerValidation(true);
                if (Object.keys(validationErrors).length > 0) {
                    setIsLoading(false);
                    return;
                }
                try {
                    const payload = {
                        description: jobData.description,
                        id: searchParams.get('id'),
                    }

                    const response = await editJobDescription(payload);
                    console.log(response);

                    if (response.status === true) {
                        if (shouldContinue) {
                            setCurrentStep(currentStep + 1);
                        } else {
                            router.push('/recruitment/job-postings');
                        }
                    }
                } catch (error) {
                    errorHandlers.jobDescription(error);
                    setIsLoading(false);
                    return;
                }
            }

            if (currentStep === 3) {
                try {
                    await applicantQuestionsRef.current?.saveQuestions();
                    if (shouldContinue) {
                        setCurrentStep(currentStep + 1);
                    } else {
                        router.push('/recruitment/job-postings');
                    }
                } catch (error) {
                    errorHandlers.custom(error, 'Error saving questions');
                    setIsLoading(false);
                    return;
                }
            }

            if (currentStep === 4) {
                if (shouldContinue) {
                    setCurrentStep(currentStep + 1);
                } else {
                    router.push('/recruitment/job-postings');
                }
            }

            if (currentStep === 5) {
                try {
                    const response = await publishJob(searchParams.get('id') || '');
                    if (response.data.status) {
                        if (shouldContinue) {
                            setCurrentStep(currentStep + 1);
                        } else {
                            router.push('/recruitment/job-postings');
                        }
                    }
                    else {
                        errorHandlers.jobPosting(response.data.message);
                        setIsLoading(false);
                        return;
                    }
                } catch (error) {
                    errorHandlers.jobPosting(error);
                    setIsLoading(false);
                    return;
                }
            }
        } finally {
            setIsLoading(false);
        }
    };

    /**
     * Handles transition to the next step with validation.
     * @returns void
     */
    const handleContinue = async (): Promise<void> => {
        await handleStepOperation(true);
    };

    /**
     * Saves current step data and redirects user to job postings page.
     * @returns void
     */
    const handleSaveExit = async (): Promise<void> => {
        await handleStepOperation(false);
    };

    return (
        <>
            {currentStep !== 6 && (
                <div className="w-full" id="create-job-root" data-testid="create-job-root">
                    <div className="flex justify-between items-center">
                        <div>
                            <p
                                className="text-[12px]/[20px] text-[#a5a5a5]"
                                id="breadcrumb-text"
                                data-testid="breadcrumb-text"
                            >
                                Job Postings <span className="text-[#0d978b]">/ Create Job Post</span>
                            </p>
                            <h1
                                className="text-[24px]/[30px] font-semibold text-[#1c1c1c] mt-[4px]"
                                id="page-title"
                                data-testid="page-title"
                            >
                                Create Job Post
                            </h1>
                        </div>
                        <div className="flex sm:gap-[19px] sm:flex-row flex-col">
                            <Button
                                variant="foreground"
                                className="h-[42px] order-2 sm:order-1 font-semibold text-[14px]/[20px] text-[#4b4b4b]"
                                id="save-exit-button"
                                data-testid="save-exit-button"
                                onClick={handleSaveExit}
                                disabled={isLoading}
                            >
                                {isLoading ? (
                                    <div className="flex items-center gap-2">
                                        <div className="w-4 h-4 border-2 border-gray-500 border-t-transparent rounded-full animate-spin"></div>
                                        Saving...
                                    </div>
                                ) : (
                                    'Save & Exit'
                                )}
                            </Button>
                            <Button
                                className="h-[42px] order-1 min-w-[82px] sm:order-2 font-semibold text-[14px]/[20px]"
                                id="save-continue-button"
                                data-testid="save-continue-button"
                                onClick={handleContinue}
                                disabled={isLoading}
                            >
                                {isLoading ? (
                                    <div className="flex items-center gap-2">
                                        <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                        {currentStep === 5 ? 'Publishing...' : 'Saving...'}
                                    </div>
                                ) : (
                                    currentStep === 5 ? 'Publish' : 'Save & Continue'
                                )}
                            </Button>
                        </div>
                    </div>

                    <div
                        className="mt-[24px] bg-white border border-[#e9e9e9] rounded-[12px] w-full flex justify-center"
                        id="create-job-container"
                        data-testid="create-job-container"
                    >
                        <div className="flex-1 border-r border-[#e9e9e9] pt-[42px] px-[48px] pb-[54px] sm:block hidden">
                            <Stepper currentStep={currentStep} />
                        </div>

                        <div
                            className="xl:w-[55vw] lg:w-[45vw] sm:w-[60vw] w-[85vw] pt-[42px] pb-[54px] pl-[20px] pr-[20px] md:pl-[48px] md:pr-[48px] sm:pl-[77px] sm:pr-[48px]"
                            id="stepper-content"
                            data-testid="stepper-content"
                        >
                            {currentStep === 1 && (
                                <JobDetails
                                    jobData={jobData}
                                    setJobData={setJobData}
                                    errors={errors}
                                    triggerValidation={triggerValidation}
                                />
                            )}
                            {currentStep === 2 && (
                                <JobDescription
                                    jobData={jobData}
                                    setJobData={setJobData}
                                    errors={descriptionError}
                                    triggerValidation={triggerValidation}
                                />
                            )}
                            {currentStep === 3 && <ApplicantQuestions ref={applicantQuestionsRef} jobId={searchParams.get('id') || undefined} />}
                            {currentStep === 4 && <HiringPipeline />}
                            {currentStep === 5 && <Publish jobData={publishJobData} onNavigateToStep={setCurrentStep} />}
                        </div>
                    </div>
                </div>
            )}
            {currentStep === 6 && (
                <Completed />
            )}
        </>
    )
}
