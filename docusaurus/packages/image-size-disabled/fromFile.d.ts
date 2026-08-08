export type ImageSize = {
    width?: number;
    height?: number;
    type?: string;
};

export declare function imageSizeFromFile(path: string): Promise<ImageSize>;
