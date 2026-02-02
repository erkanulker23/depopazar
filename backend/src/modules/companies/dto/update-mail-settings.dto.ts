import { IsOptional, IsString, IsInt, IsBoolean, IsEmail, Min, Max } from 'class-validator';
import { ApiPropertyOptional } from '@nestjs/swagger';

export class UpdateMailSettingsDto {
  @ApiPropertyOptional({ description: 'SMTP host' })
  @IsOptional()
  @IsString()
  smtp_host?: string;

  @ApiPropertyOptional({ description: 'SMTP port' })
  @IsOptional()
  @IsInt()
  @Min(1)
  @Max(65535)
  smtp_port?: number;

  @ApiPropertyOptional({ description: 'SMTP secure (SSL/TLS)' })
  @IsOptional()
  @IsBoolean()
  smtp_secure?: boolean;

  @ApiPropertyOptional({ description: 'SMTP username' })
  @IsOptional()
  @IsString()
  smtp_username?: string;

  @ApiPropertyOptional({ description: 'SMTP password' })
  @IsOptional()
  @IsString()
  smtp_password?: string;

  @ApiPropertyOptional({ description: 'From email address' })
  @IsOptional()
  @IsEmail()
  from_email?: string;

  @ApiPropertyOptional({ description: 'From name' })
  @IsOptional()
  @IsString()
  from_name?: string;

  @ApiPropertyOptional({ description: 'Contract created email template' })
  @IsOptional()
  @IsString()
  contract_created_template?: string;

  @ApiPropertyOptional({ description: 'Payment received email template' })
  @IsOptional()
  @IsString()
  payment_received_template?: string;

  @ApiPropertyOptional({ description: 'Contract expiring email template' })
  @IsOptional()
  @IsString()
  contract_expiring_template?: string;

  @ApiPropertyOptional({ description: 'Payment reminder email template' })
  @IsOptional()
  @IsString()
  payment_reminder_template?: string;

  @ApiPropertyOptional({ description: 'Welcome email template' })
  @IsOptional()
  @IsString()
  welcome_template?: string;

  @ApiPropertyOptional({ description: 'Notify customer on contract creation' })
  @IsOptional()
  @IsBoolean()
  notify_customer_on_contract?: boolean;

  @ApiPropertyOptional({ description: 'Notify customer on payment received' })
  @IsOptional()
  @IsBoolean()
  notify_customer_on_payment?: boolean;

  @ApiPropertyOptional({ description: 'Notify customer on overdue' })
  @IsOptional()
  @IsBoolean()
  notify_customer_on_overdue?: boolean;

  @ApiPropertyOptional({ description: 'Notify admin on contract creation' })
  @IsOptional()
  @IsBoolean()
  notify_admin_on_contract?: boolean;

  @ApiPropertyOptional({ description: 'Notify admin on payment received' })
  @IsOptional()
  @IsBoolean()
  notify_admin_on_payment?: boolean;

  @ApiPropertyOptional({ description: 'Admin contract created template' })
  @IsOptional()
  @IsString()
  admin_contract_created_template?: string;

  @ApiPropertyOptional({ description: 'Admin payment received template' })
  @IsOptional()
  @IsString()
  admin_payment_received_template?: string;

  @ApiPropertyOptional({ description: 'Is mail settings active' })
  @IsOptional()
  @IsBoolean()
  is_active?: boolean;
}
