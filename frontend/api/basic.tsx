import axios from "axios";

export const getCountry = async (): Promise<any> => {
    return axios.get(`${process.env.NEXT_PUBLIC_BASE_PATH}/country`);
};

export const getDepartment = async (): Promise<any> => {
    return axios.get(`${process.env.NEXT_PUBLIC_BASE_PATH}/master/list-with-filters?type_id=1`);
};

export const getDesignation = async (): Promise<any> => {
    return axios.get(`${process.env.NEXT_PUBLIC_BASE_PATH}/master/list-with-filters?type_id=2`);
};

export const getEmploymentType = async (): Promise<any> => {
    return axios.get(`${process.env.NEXT_PUBLIC_BASE_PATH}/master/list-with-filters?type_id=3`);
};

export const getSkills = async (): Promise<any> => {
    return axios.get(`${process.env.NEXT_PUBLIC_BASE_PATH}/master/list-with-filters?type_id=4`);
};

export const addNewJobTitle = async (title: string) => {
    const payload = {
        name: title,
        type_id: 2
    };
    const response = await axios.post(`${process.env.NEXT_PUBLIC_BASE_PATH}/master/add`, payload);
    return response;
}
