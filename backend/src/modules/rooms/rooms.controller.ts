import { Controller, Get, Post, Body, Patch, Param, Delete, UseGuards, BadRequestException, Logger } from '@nestjs/common';
import { ApiTags, ApiOperation, ApiBearerAuth } from '@nestjs/swagger';
import { RoomsService } from './rooms.service';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { RolesGuard } from '../auth/guards/roles.guard';
import { Roles } from '../auth/decorators/roles.decorator';
import { UserRole } from '../../common/enums/user-role.enum';
import { CreateRoomDto } from './dto/create-room.dto';

@ApiTags('Rooms')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard, RolesGuard)
@Controller('rooms')
export class RoomsController {
  private readonly logger = new Logger(RoomsController.name);

  constructor(private readonly roomsService: RoomsService) {}

  @Post()
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY)
  @ApiOperation({ summary: 'Create a new room' })
  async create(@Body() createRoomDto: CreateRoomDto) {
    try {
      return await this.roomsService.create(createRoomDto);
    } catch (error: unknown) {
      this.logger.error('Error creating room', error instanceof Error ? error.stack : String(error));
      throw new BadRequestException(error instanceof Error ? error.message : 'Failed to create room');
    }
  }

  @Get()
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY, UserRole.ACCOUNTING)
  @ApiOperation({ summary: 'Get all rooms' })
  async findAll() {
    return this.roomsService.findAll();
  }

  @Post('bulk-delete')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY)
  @ApiOperation({ summary: 'Delete multiple rooms' })
  async bulkDelete(@Body() body: { ids: string[] }) {
    for (const id of body.ids) {
      await this.roomsService.remove(id);
    }
    return { message: `${body.ids.length} room(s) deleted successfully` };
  }

  @Get(':id')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY, UserRole.ACCOUNTING)
  @ApiOperation({ summary: 'Get room by ID' })
  async findOne(@Param('id') id: string) {
    return this.roomsService.findOne(id);
  }

  @Patch(':id')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY)
  @ApiOperation({ summary: 'Update room' })
  async update(@Param('id') id: string, @Body() updateRoomDto: any) {
    return this.roomsService.update(id, updateRoomDto);
  }

  @Delete(':id')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY)
  @ApiOperation({ summary: 'Delete room' })
  async remove(@Param('id') id: string) {
    try {
      await this.roomsService.remove(id);
      return { message: 'Room deleted successfully' };
    } catch (error: unknown) {
      this.logger.error('Error deleting room', error instanceof Error ? error.stack : String(error));
      throw new BadRequestException(error instanceof Error ? error.message : 'Failed to delete room');
    }
  }
}
