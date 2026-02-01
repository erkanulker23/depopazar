import { Controller, Get, Post, Body, Patch, Param, Delete, UseGuards, BadRequestException } from '@nestjs/common';
import { ApiTags, ApiOperation, ApiBearerAuth } from '@nestjs/swagger';
import { RoomsService } from './rooms.service';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { CreateRoomDto } from './dto/create-room.dto';

@ApiTags('Rooms')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard)
@Controller('rooms')
export class RoomsController {
  constructor(private readonly roomsService: RoomsService) {}

  @Post()
  @ApiOperation({ summary: 'Create a new room' })
  async create(@Body() createRoomDto: CreateRoomDto) {
    try {
      return await this.roomsService.create(createRoomDto);
    } catch (error: any) {
      console.error('Error creating room:', error);
      throw new BadRequestException(error?.message || 'Failed to create room');
    }
  }

  @Get()
  @ApiOperation({ summary: 'Get all rooms' })
  async findAll() {
    return this.roomsService.findAll();
  }

  @Post('bulk-delete')
  @ApiOperation({ summary: 'Delete multiple rooms' })
  async bulkDelete(@Body() body: { ids: string[] }) {
    for (const id of body.ids) {
      await this.roomsService.remove(id);
    }
    return { message: `${body.ids.length} room(s) deleted successfully` };
  }

  @Get(':id')
  @ApiOperation({ summary: 'Get room by ID' })
  async findOne(@Param('id') id: string) {
    return this.roomsService.findOne(id);
  }

  @Patch(':id')
  @ApiOperation({ summary: 'Update room' })
  async update(@Param('id') id: string, @Body() updateRoomDto: any) {
    return this.roomsService.update(id, updateRoomDto);
  }

  @Delete(':id')
  @ApiOperation({ summary: 'Delete room' })
  async remove(@Param('id') id: string) {
    try {
      await this.roomsService.remove(id);
      return { message: 'Room deleted successfully' };
    } catch (error: any) {
      console.error('Error deleting room:', error);
      throw new BadRequestException(error?.message || 'Failed to delete room');
    }
  }
}
