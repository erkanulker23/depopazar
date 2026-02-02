import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { ServiceCategory } from './entities/service-category.entity';
import { Service } from './entities/service.entity';
import { ServiceCategoriesService } from './service-categories.service';
import { ServicesService } from './services.service';
import { ServiceCategoriesController } from './service-categories.controller';
import { ServicesController } from './services.controller';
import { CompaniesModule } from '../companies/companies.module';

@Module({
  imports: [
    TypeOrmModule.forFeature([ServiceCategory, Service]),
    CompaniesModule,
  ],
  controllers: [ServiceCategoriesController, ServicesController],
  providers: [ServiceCategoriesService, ServicesService],
  exports: [ServicesService],
})
export class ServicesModule {}
