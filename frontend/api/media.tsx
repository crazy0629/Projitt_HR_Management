import axios from "axios";

export const uploadMedia = async (data: any): Promise<any> => {
    return axios.post(`${process.env.NEXT_PUBLIC_BASE_PATH}/media/add`, data, {
        headers: {
            'Content-Type': 'multipart/form-data',
        },
    });
};
