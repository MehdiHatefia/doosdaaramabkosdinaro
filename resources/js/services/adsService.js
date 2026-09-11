import { adService } from './adService';
import { bankService } from './bankService';

export const fetchList = (params = {}) => adService.getAll(params);
export const fetchBanks = () => bankService.getAll();