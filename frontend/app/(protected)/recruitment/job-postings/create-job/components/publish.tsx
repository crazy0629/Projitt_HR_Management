/**
 * Publish Component
 * 
 * This component renders a review screen for a job posting,
 * displaying job details, description, and applicant questions,
 * with edit options for each section. It helps users verify all inputs
 * before publishing a job listing.
 */

import { Edit } from "lucide-react";

interface PublishProps {
    jobData?: Record<string, unknown>;
    onNavigateToStep?: (step: number) => void;
}

/**
 * Renders the Publish component for reviewing job post details.
 */
export default function Publish({ jobData, onNavigateToStep }: PublishProps) {
    // Default data if no jobData is provided
    const defaultData = jobData || {
        title: "Software Engineer",
        department: { name: "Sales" },
        employment_type: { name: "Full-time" },
        no_of_job_opening: 3,
        skills: [
            { name: "Data Analysis" },
            { name: "UI/UX" },
            { name: "Prototyping" },
            { name: "Wireframing" }
        ],
        location_type: { name: "Onsite" },
        country: { name: "United States" },
        state: "California",
        salary_from: "5000",
        salary_to: "6000",
        deadline: "2025-06-10T00:00:00.000000Z",
        description: "Senior Data Analyst Position Overview<br />We are seeking an experienced Senior Data Analyst to join our growing analytics team. The ideal candidate will transform complex data into actionable insights that drive business decisions.<br />Key Responsibilities:<br />Lead complex data analysis projects and develop comprehensive reporting solutions<br />Build and maintain advanced statistical models and data visualization dashboards<br />Collaborate with stakeholders to identify business needs and translate them into analytical solutions<br />Mentor junior analysts and promote best practices in data analysis<br />Design and implement data quality processes and validation procedures<br />Required Qualifications:<br />Bachelor&apos;s degree in Statistics, Mathematics, Computer Science, or related field<br />5+ years of experience in data analysis and visualization<br />Expert proficiency in SQL, Python, or R<br />Strong experience with BI tools (Tableau, Power BI, or similar)<br />Proven track record of delivering data-driven solutions",
        questions: [
            {
                question_name: "Why are you interested in this role?",
                answer_type: "short"
            },
            {
                question_name: "Describe a recent challenge you solved at work.",
                answer_type: "long_detail"
            },
            {
                question_name: "What's your expected salary range for this role?",
                answer_type: "long_detail"
            },
            {
                question_name: "How soon can you start if hired?",
                answer_type: "dropdown",
                options: ["Immediately", "1–2 weeks", "1 month", "Other"]
            },
            {
                question_name: "Have you worked in a team setting before?",
                answer_type: "checkbox",
                options: ["Yes", "No"]
            }
        ]
    };
    const data = jobData || {
        title: "",
        department: { name: "" },
        employment_type: { name: "" },
        no_of_job_opening: 1,
        skills: [],
        location_type: { name: "" },
        country: { name: "" },
        state: "",
        salary_from: "",
        salary_to: "",
        deadline: "",
        description: "",
        questions: []
    };

    // Helper function to format date
    const formatDate = (dateString?: string) => {
        if (!dateString) return "";
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    };

    // Navigation handlers
    const handleEditJobDetails = () => {
        if (onNavigateToStep) {
            onNavigateToStep(1);
        }
    };

    const handleEditJobDescription = () => {
        if (onNavigateToStep) {
            onNavigateToStep(2);
        }
    };

    const handleEditApplicantQuestions = () => {
        if (onNavigateToStep) {
            onNavigateToStep(3);
        }
    };

    // Helper function to get answer type display text
    const getAnswerTypeDisplay = (answerType: string) => {
        switch (answerType) {
            case "short":
                return "Short Answer";
            case "long_detail":
                return "Long Paragraph";
            case "dropdown":
                return "Multiple choice";
            case "checkbox":
                return "Multiple choice";
            case "file_upload":
                return "File Upload";
            default:
                return answerType;
        }
    };

    // Helper function to render options for dropdown/checkbox
    const renderOptions = (question: Record<string, unknown>) => {
        if (question.answer_type === "dropdown" || question.answer_type === "checkbox") {
            if (question.options && Array.isArray(question.options)) {
                return question.options.join(", ");
            }
        }
        return "";
    };

    return (
        <div
            id="job-description-component"
            data-testid="job-description-component"
        >
            <h1
                className="text-[20px]/[30px] font-semibold text-[#353535]"
                id="job-description-title"
                data-testid="job-description-title"
            >
                Review & Publish
            </h1>

            {/* Job Details Section */}
            <div className="xl:w-[650px] w-full flex flex-col gap-[36px] mt-[50px]">
                <div
                    className="border border-[#e9e9e9] rounded-[12px] bg-[#fafafa] p-[20px]"
                    id="job-details-section"
                    data-testid="job-details-section"
                >
                    <div className="flex justify-between">
                        <p className="text-[16px]/[24px] font-medium text-[#1c1c1c]">Job Details</p>
                        <button
                            className="font-medium bg-[#0d978b] text-white rounded-[7px] w-[28px] h-[28px] flex items-center justify-center"
                            id="edit-job-details"
                            data-testid="edit-job-details"
                            onClick={handleEditJobDetails}
                        >
                            <Edit className="size-[16px]" />
                        </button>
                    </div>
                    <div className="grid md:grid-cols-3 sm:grid-cols-2 mt-[12px] gap-[42px]" id="job-details-fields" data-testid="job-details-fields">
                        <div className="flex flex-col" id="field-job-title" data-testid="field-job-title">
                            <p className="text-[14px]/[22px] font-medium text-[#a5a5a5]">Job Title</p>
                            <p className="text-[14px]/[22px] font-medium text-[#353535]">{String(data.title || "N/A")}</p>
                        </div>
                        <div className="flex flex-col" id="field-department" data-testid="field-department">
                            <p className="text-[14px]/[22px] font-medium text-[#a5a5a5]">Department</p>
                            <p className="text-[14px]/[22px] font-medium text-[#353535]">{data.department && typeof data.department === 'object' && 'name' in data.department ? data.department.name : "N/A"}</p>
                        </div>
                        <div className="flex flex-col" id="field-employment-type" data-testid="field-employment-type">
                            <p className="text-[14px]/[22px] font-medium text-[#a5a5a5]">Employment Type</p>
                            <p className="text-[14px]/[22px] font-medium text-[#353535]">{data.employment_type && typeof data.employment_type === 'object' && 'name' in data.employment_type ? data.employment_type.name : "N/A"}</p>
                        </div>
                        <div className="flex flex-col" id="field-openings" data-testid="field-openings">
                            <p className="text-[14px]/[22px] font-medium text-[#a5a5a5]">No. of Openings</p>
                            <p className="text-[14px]/[22px] font-medium text-[#353535]">{data.no_of_job_opening || "N/A"}</p>
                        </div>
                        <div className="flex flex-col" id="field-skills" data-testid="field-skills">
                            <p className="text-[14px]/[22px] font-medium text-[#a5a5a5]">Skills</p>
                            <div>
                                {data.skills && Array.isArray(data.skills) ? (
                                    data.skills.map((skill: any, index: number) => (
                                        <p key={index} className="text-[14px]/[22px] font-medium text-[#353535]">
                                            {skill.name}
                                        </p>
                                    ))
                                ) : (
                                    <p className="text-[14px]/[22px] font-medium text-[#353535]">N/A</p>
                                )}
                            </div>
                        </div>
                        <div className="flex flex-col" id="field-location" data-testid="field-location">
                            <p className="text-[14px]/[22px] font-medium text-[#a5a5a5]">Location Type</p>
                            <p className="text-[14px]/[22px] font-medium text-[#353535]">
                                {data.location_type && typeof data.location_type === 'object' && 'name' in data.location_type ? data.location_type.name : "N/A"}
                                {data.state && data.country && typeof data.country === 'object' && 'name' in data.country ? ` (${data.state}, ${data.country.name})` : ""}
                            </p>
                        </div>
                        <div className="flex flex-col" id="field-salary" data-testid="field-salary">
                            <p className="text-[14px]/[22px] font-medium text-[#a5a5a5]">Salary</p>
                            <p className="text-[14px]/[22px] font-medium text-[#353535]">
                                {data.salary_from && data.salary_to ?
                                    `$${parseInt(data.salary_from).toLocaleString()} - $${parseInt(data.salary_to).toLocaleString()}` :
                                    "N/A"
                                }
                            </p>
                        </div>
                        <div className="flex flex-col" id="field-deadline" data-testid="field-deadline">
                            <p className="text-[14px]/[22px] font-medium text-[#a5a5a5]">Deadline</p>
                            <p className="text-[14px]/[22px] font-medium text-[#353535]">{formatDate(data.deadline)}</p>
                        </div>
                    </div>
                </div>
            </div>

            {/* Job Description Section */}
            <div className="md:w-[650px] w-full flex flex-col gap-[36px] mt-[50px]">
                <div
                    className="border border-[#e9e9e9] rounded-[12px] bg-[#fafafa] p-[20px]"
                    id="job-description-section"
                    data-testid="job-description-section"
                >
                    <div className="flex justify-between">
                        <p className="text-[16px]/[24px] font-medium text-[#1c1c1c]">Job Description</p>
                        <button
                            className="font-medium bg-[#0d978b] text-white rounded-[7px] w-[28px] h-[28px] flex items-center justify-center"
                            id="edit-job-description"
                            data-testid="edit-job-description"
                            onClick={handleEditJobDescription}
                        >
                            <Edit className="size-[16px]" />
                        </button>
                    </div>
                    <div className="flex flex-col mt-[16px]" id="job-description-text" data-testid="job-description-text">
                        <p className="text-[14px]/[22px] font-medium text-[#a5a5a5]">Job Description</p>
                        <div
                            className="text-[14px]/[22px] font-medium text-[#353535]"
                            dangerouslySetInnerHTML={{ __html: data.description || "No description available" }}
                        />
                    </div>
                </div>
            </div>

            {/* Applicant Questions Section */}
            <div className="md:w-[650px] w-full flex flex-col gap-[36px] mt-[50px]">
                <div
                    className="border border-[#e9e9e9] rounded-[12px] bg-[#fafafa] p-[20px]"
                    id="applicant-questions-section"
                    data-testid="applicant-questions-section"
                >
                    <div className="flex justify-between">
                        <p className="text-[16px]/[24px] font-medium text-[#1c1c1c]">Applicant Questions</p>
                        <button
                            className="font-medium bg-[#0d978b] text-white rounded-[7px] w-[28px] h-[28px] flex items-center justify-center"
                            id="edit-applicant-questions"
                            data-testid="edit-applicant-questions"
                            onClick={handleEditApplicantQuestions}
                        >
                            <Edit className="size-[16px]" />
                        </button>
                    </div>
                    <div className="flex flex-col mt-[16px] gap-[14px]" id="applicant-questions" data-testid="applicant-questions">
                        {data.questions && Array.isArray(data.questions) ? (
                            data.questions.map((question: any, index: number) => (
                                <div
                                    key={index}
                                    className="text-[14px]/[22px] font-medium text-[#353535]"
                                    data-testid={`question-${index + 1}`}
                                >
                                    <p>{question.question_name}</p>
                                    <p className="text-[#a5a5a5]">
                                        {getAnswerTypeDisplay(question.answer_type)}
                                        {renderOptions(question) && `: ${renderOptions(question)}`}
                                    </p>
                                </div>
                            ))
                        ) : (
                            <p className="text-[14px]/[22px] font-medium text-[#353535]">No questions available</p>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}