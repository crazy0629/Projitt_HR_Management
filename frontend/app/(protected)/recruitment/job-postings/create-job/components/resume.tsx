'use client'
import { useState } from "react";
import FileDropUpload from "./file-drop-upload";

/**
 * Resume component renders file upload inputs for required resume/CV and optional other documents.
 * It uses the FileDropUpload component and manages the selected file states.
 */
export default function Resume() {
    const [resume, setResume] = useState<File | null>(null)
    const [otherDocuments, setOtherDocuments] = useState<File | null>(null)

    return (
        <div data-test-id="resume-upload-container">
            <p className="text-[#a5a5a5] text-[16px]/[24px]" data-test-id="resume-instruction-text">
                All applicants are required to upload a resume or CV before proceeding. This step is locked and cannot be modified.
            </p>
            <div className="mt-[19px]" data-test-id="file-upload-sections">
                <FileDropUpload
                    label="Resume"
                    setFile={setResume}
                    file={resume}
                />
                <FileDropUpload
                    label="Other documents"
                    setFile={setOtherDocuments}
                    file={otherDocuments}
                />
            </div>
        </div>
    );
}
