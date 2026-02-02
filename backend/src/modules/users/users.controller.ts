import { Controller, Get, Body, Patch, Param, Delete, UseGuards, BadRequestException, ForbiddenException } from '@nestjs/common';
import { ApiTags, ApiOperation, ApiBearerAuth } from '@nestjs/swagger';
import { UsersService } from './users.service';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { RolesGuard } from '../auth/guards/roles.guard';
import { Roles } from '../auth/decorators/roles.decorator';
import { UserRole } from '../../common/enums/user-role.enum';
import { UpdateUserDto } from './dto/update-user.dto';
import { CurrentUser } from '../auth/decorators/current-user.decorator';
import { CompaniesService } from '../companies/companies.service';

@ApiTags('Users')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard, RolesGuard)
@Controller('users')
export class UsersController {
  constructor(
    private readonly usersService: UsersService,
    private readonly companiesService: CompaniesService,
  ) {}

  @Get()
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER)
  @ApiOperation({ summary: 'Get all users' })
  async findAll(@CurrentUser() user: any) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId && user.role !== UserRole.SUPER_ADMIN) {
      throw new BadRequestException('User has no company');
    }
    return this.usersService.findAll(companyId || undefined);
  }

  @Get(':id')
  @ApiOperation({ summary: 'Get user by ID' })
  async findOne(@Param('id') id: string, @CurrentUser() user: any) {
    const targetUser = await this.usersService.findOne(id);
    
    // SUPER_ADMIN can access any user
    if (user.role === UserRole.SUPER_ADMIN) {
      return targetUser;
    }

    // Other users can only access users from their company
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) {
      throw new BadRequestException('User has no company');
    }

    if (targetUser.company_id !== companyId) {
      throw new ForbiddenException('Bu kullanıcıya erişim yetkiniz yok');
    }

    return targetUser;
  }

  @Patch(':id')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER)
  @ApiOperation({ summary: 'Update user' })
  async update(@Param('id') id: string, @Body() updateUserDto: UpdateUserDto, @CurrentUser() user: any) {
    const targetUser = await this.usersService.findOne(id);
    
    // SUPER_ADMIN can update any user
    if (user.role !== UserRole.SUPER_ADMIN) {
      const companyId = await this.companiesService.getCompanyIdForUser(user);
      if (!companyId) {
        throw new BadRequestException('User has no company');
      }

      // COMPANY_OWNER can only update users from their company
      if (targetUser.company_id !== companyId) {
        throw new ForbiddenException('Bu kullanıcıyı güncelleyemezsiniz');
      }
    }

    return this.usersService.update(id, updateUserDto, user);
  }

  @Patch(':id/reset-password')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER)
  @ApiOperation({ summary: 'Reset user password' })
  async resetPassword(@Param('id') id: string, @Body() body: { password?: string }, @CurrentUser() user: any) {
    const targetUser = await this.usersService.findOne(id);
    
    // Authorization checks
    if (user.role !== UserRole.SUPER_ADMIN) {
      const companyId = await this.companiesService.getCompanyIdForUser(user);
      if (!companyId || targetUser.company_id !== companyId) {
        throw new ForbiddenException('Bu işlem için yetkiniz yok');
      }
    }

    const newPassword = body.password || Math.random().toString(36).slice(-8);
    await this.usersService.update(id, { password: newPassword }, user);
    
    return { message: 'Password reset successfully', password: newPassword };
  }

  @Delete(':id')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER)
  @ApiOperation({ summary: 'Delete user' })
  async remove(@Param('id') id: string, @CurrentUser() user: any) {
    const targetUser = await this.usersService.findOne(id);
    
    // SUPER_ADMIN can delete any user
    if (user.role !== UserRole.SUPER_ADMIN) {
      const companyId = await this.companiesService.getCompanyIdForUser(user);
      if (!companyId) {
        throw new BadRequestException('User has no company');
      }

      // COMPANY_OWNER can only delete users from their company
      if (targetUser.company_id !== companyId) {
        throw new ForbiddenException('Bu kullanıcıyı silemezsiniz');
      }
    }

    await this.usersService.remove(id);
    return { message: 'User deleted successfully' };
  }
}
